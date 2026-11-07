<?php

namespace App\Http\Controllers;

use App\Events\MatchSignal;
use App\Models\MatchEvent;
use App\Models\User;
use App\Models\VideoMatch;
use App\Services\AnalyticsRecorder;
use App\Services\MatchmakingService;
use App\Services\PresenceService;
use App\Services\SafetyService;
use App\Support\Countries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchController extends Controller
{
    private const QUEUE_STALE_SECONDS = 20;

    private const CALL_STALE_SECONDS = 45;

    private const MAX_CANDIDATES = 300;

    public function find(Request $request): JsonResponse
    {
        if ($denied = $this->denied($request->user())) {
            return $denied;
        }

        $token = $this->token($request);
        $match = $this->enqueue($request->user(), $token);
        app(AnalyticsRecorder::class)->track($request->user(), 'find_stranger');
        app(AnalyticsRecorder::class)->track($request->user(), 'search_started');
        app(AnalyticsRecorder::class)->track($request->user(), 'match_search_started');
        if ($match->status === VideoMatch::WAITING) {
            app(AnalyticsRecorder::class)->track($request->user(), 'search_wait');
        }

        return $this->json($this->present($match, $request->user(), 0));
    }

    public function poll(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $user = $request->user();
        $cursor = max(0, (int) $request->query('cursor', 0));
        $this->expireAbandonedSometimes();
        $match = $this->matchByToken($user, $token);

        if (! $match) {
            $status = $this->activeFor($user) ? 'replaced' : 'idle';

            return $this->json(['status' => $status]);
        }

        if ($match->status === VideoMatch::WAITING) {
            $match = $this->adoptWaiter($user, $match);
        }

        if ($match->status !== VideoMatch::ENDED) {
            $match = $this->heartbeat($match, $user);
        }

        return $this->json($this->present($match, $user, $cursor));
    }

    public function signal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'type' => ['required', 'in:offer,answer,candidate,connected,failed'],
            'payload' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $match = $this->matchByToken($user, $data['token']);

        if (! $match || $match->status === VideoMatch::ENDED || $match->user_two_id === null) {
            return $this->json(['message' => 'Match is no longer active.'], 409);
        }

        $type = $data['type'];
        $payload = $data['payload'] ?? [];
        $isOfferer = $match->user_one_id === $user->id;

        if ($type === 'offer' && ! $isOfferer) {
            abort(403, 'You cannot send that signal.');
        }

        if ($type === 'answer' && $isOfferer) {
            abort(403, 'You cannot send that signal.');
        }

        $version = 0;
        DB::transaction(function () use ($match, $user, $type, $payload, $isOfferer, &$version) {
            $locked = VideoMatch::query()->whereKey($match->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === VideoMatch::ENDED || $locked->user_two_id === null) {
                abort(409, 'Match is no longer active.');
            }

            $signal = $locked->signal ?? ['offer' => null, 'answer' => null, 'candidates' => []];

            if ($type === 'offer') {
                $description = $this->sessionDescription($payload, 'offer');
                $previous = $signal['offer']['sdp'] ?? null;
                $current = (int) ($signal['v'] ?? 0);
                if (! is_string($previous)) {
                    $signal['v'] = $current > 0 ? $current : 1;
                } elseif ($previous !== $description['sdp']) {
                    $signal['v'] = $current + 1;
                    $signal['answer'] = null;
                    $signal['candidates'] = [];
                }
                $signal['offer'] = $description;
            } elseif ($type === 'answer') {
                $given = array_key_exists('v', $payload) ? (int) $payload['v'] : null;
                $current = (int) ($signal['v'] ?? 0);
                if ($given === null || $current === 0 || $given === $current) {
                    $signal['answer'] = $signal['answer'] ?? $this->sessionDescription($payload, 'answer');
                }
            } elseif ($type === 'candidate') {
                $given = array_key_exists('v', $payload) ? (int) $payload['v'] : null;
                $current = (int) ($signal['v'] ?? 0);
                $candidates = $signal['candidates'] ?? [];

                if (($given === null || $current === 0 || $given === $current) && count($candidates) < self::MAX_CANDIDATES) {
                    $lastId = 0;

                    foreach ($candidates as $item) {
                        $lastId = max($lastId, (int) ($item['id'] ?? 0));
                    }

                    $candidates[] = [
                        'id' => $lastId + 1,
                        'user_id' => $user->id,
                        'candidate' => $this->iceCandidate($payload),
                    ];
                    $signal['candidates'] = $candidates;
                }
            } elseif ($type === 'failed') {
                $locked->status = VideoMatch::FAILED;
                $locked->end_reason = 'failed';
            } elseif ($locked->status === VideoMatch::CONNECTING) {
                $locked->status = VideoMatch::CONNECTED;
            }

            if ($isOfferer) {
                $locked->user_one_seen_at = now();
            } else {
                $locked->user_two_seen_at = now();
            }

            $locked->signal = $signal;
            $locked->save();
            $version = (int) ($signal['v'] ?? 0);
            $this->afterSignal($locked, $user, $type);
        });

        return $this->json(['ok' => true, 'v' => $version]);
    }

    public function next(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'next_token' => ['required', 'string', 'max:64'],
        ]);
        $user = $request->user();
        if ($denied = $this->denied($user)) {
            return $denied;
        }

        $this->expireAbandonedSometimes();
        $this->endTokenMatch($user, $data['token'], 'next');
        $match = $this->pairOrQueue($user, $data['next_token']);

        return $this->json($this->present($match, $user, 0));
    }

    public function end(Request $request): JsonResponse
    {
        return $this->finish($request, 'end');
    }

    public function leave(Request $request): JsonResponse
    {
        return $this->finish($request, 'disconnect');
    }

    private function finish(Request $request, string $reason): JsonResponse
    {
        $token = $this->token($request);
        $this->endTokenMatch($request->user(), $token, $reason);

        return $this->json(['status' => VideoMatch::ENDED, 'end_reason' => $reason]);
    }

    private function enqueue(User $user, string $token): VideoMatch
    {
        $this->expireAbandonedSometimes();

        $existing = DB::transaction(function () use ($user, $token) {
            $active = VideoMatch::query()->active()->forUser($user->id)->orderBy('id')->lockForUpdate()->get();
            $same = $active->first(fn (VideoMatch $match) => $match->tokenFor($user->id) === $token);

            if ($same && $active->count() === 1) {
                $this->touch($same, $user->id);
                $same->save();

                return $same;
            }

            foreach ($active as $match) {
                $match->status = VideoMatch::ENDED;
                $match->end_reason = 'disconnect';
                $match->save();
            }

            return null;
        });

        return $existing ?? $this->pairOrQueue($user, $token);
    }

    /**
     * Claim one waiting person, or join the queue. Each attempt locks only the
     * rows it needs, so many people can be paired at the same time.
     */
    private function pairOrQueue(User $user, string $token): VideoMatch
    {
        $claimed = $this->claimOldest($user, $token);
        if ($claimed) {
            return $claimed;
        }

        $created = VideoMatch::query()->create([
            'user_one_id' => $user->id,
            'user_one_token' => $token,
            'user_one_seen_at' => now(),
            'status' => VideoMatch::WAITING,
            'signal' => ['offer' => null, 'answer' => null, 'candidates' => []],
        ]);
        $user->forceFill(['status' => User::SEARCHING, 'last_seen_at' => now()])->save();
        app(PresenceService::class)->touch($user->id, 'searching');

        return $created;
    }

    private function claimOldest(User $user, string $token): ?VideoMatch
    {
        $cutoff = now()->subSeconds(self::QUEUE_STALE_SECONDS);
        $ids = $this->openWaiterIds($user->id, $cutoff);

        foreach ($ids as $id) {
            $claimed = DB::transaction(function () use ($id, $user, $token) {
                $waiting = VideoMatch::query()->whereKey($id)->lockForUpdate()->first();
                if (! $this->isOpenWait($waiting)) {
                    return null;
                }

                $partner = User::query()->find($waiting->user_one_id);
                if (! app(MatchmakingService::class)->canPair($user, $partner)) {
                    return null;
                }

                $waiting->user_two_id = $user->id;
                $waiting->user_two_token = $token;
                $waiting->user_two_seen_at = now();
                $waiting->status = VideoMatch::CONNECTING;
                $waiting->save();
                $this->rememberPair($waiting, $user, (int) $waiting->user_one_id);

                return $waiting;
            });

            if ($claimed) {
                return $claimed;
            }
        }

        return null;
    }

    /**
     * People already in the queue pair from their poll. The two rows are locked
     * in id order so two searches cannot claim each other or form two calls.
     */
    private function adoptWaiter(User $user, VideoMatch $current): VideoMatch
    {
        $token = $current->tokenFor($user->id);
        if ($token === null || ! $this->isOpenWait($current)) {
            return $current;
        }

        $cutoff = now()->subSeconds(self::QUEUE_STALE_SECONDS);
        foreach ($this->openWaiterIds($user->id, $cutoff, $current->id) as $id) {
            $joined = $this->tryJoin($user, (int) $current->id, (int) $id);
            if ($joined instanceof VideoMatch) {
                return $joined;
            }
            if ($joined === false) {
                return $this->matchByToken($user, $token) ?? $current;
            }
        }

        return VideoMatch::query()->find($current->id) ?? $current;
    }

    private function tryJoin(User $user, int $mineId, int $otherId): VideoMatch|false|null
    {
        return DB::transaction(function () use ($user, $mineId, $otherId) {
            $ids = [$mineId, $otherId];
            sort($ids);
            $locked = VideoMatch::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $mine = $locked->get($mineId);
            $other = $locked->get($otherId);

            if (! $this->isOpenWait($mine)) {
                return false;
            }
            if (! $this->isOpenWait($other)) {
                return null;
            }

            $partner = User::query()->find($other->user_one_id);
            if (! app(MatchmakingService::class)->canPair($user, $partner)) {
                return null;
            }

            $older = $mine->id < $other->id ? $mine : $other;
            $newer = $older->id === $mine->id ? $other : $mine;
            $older->user_two_id = $newer->user_one_id;
            $older->user_two_token = $newer->user_one_token;
            $older->user_two_seen_at = now();
            $older->status = VideoMatch::CONNECTING;
            $older->save();

            $newer->user_one_token = null;
            $newer->status = VideoMatch::ENDED;
            $newer->end_reason = 'paired';
            $newer->ended_at = now();
            $newer->save();

            $this->rememberPair($older, $user, (int) $older->user_one_id === $user->id ? (int) $newer->user_one_id : (int) $older->user_one_id);

            return $older->fresh();
        });
    }

    /**
     * @return list<int>
     */
    private function openWaiterIds(int $userId, \Illuminate\Support\Carbon $cutoff, ?int $exceptId = null): array
    {
        $blocked = app(SafetyService::class)->blockedIds($userId);

        return VideoMatch::query()
            ->where('status', VideoMatch::WAITING)
            ->whereNull('user_two_id')
            ->where('user_one_id', '!=', $userId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->when($blocked !== [], fn ($query) => $query->whereNotIn('user_one_id', $blocked))
            ->where('user_one_seen_at', '>=', $cutoff)
            ->orderBy('id')
            ->limit(25)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function isOpenWait(?VideoMatch $match): bool
    {
        return $match !== null
            && $match->status === VideoMatch::WAITING
            && $match->user_two_id === null
            && $match->user_one_token !== null;
    }

    private function rememberPair(VideoMatch $match, User $actor, int $partnerId): void
    {
        User::query()->whereKey([$actor->id, $partnerId])->update(['status' => User::IN_CALL, 'last_seen_at' => now()]);
        MatchEvent::write($match->id, $actor->id, MatchEvent::CREATED);
        app(PresenceService::class)->touch($actor->id, 'in_call');
        app(AnalyticsRecorder::class)->track($actor, 'match_created');
        app(AnalyticsRecorder::class)->track($actor, 'match_found');
        $this->broadcast($match);
    }

    private function expireAbandonedSometimes(): void
    {
        static $next = 0.0;
        $now = microtime(true);
        if ($now < $next) {
            return;
        }
        $next = $now + 5;

        try {
            if (! Cache::add('stranger-expire-abandoned', 1, 5)) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $this->expireAbandoned();
    }

    private function heartbeat(VideoMatch $match, User $user): VideoMatch
    {
        return DB::transaction(function () use ($match, $user) {
            $locked = VideoMatch::query()->whereKey($match->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === VideoMatch::ENDED) {
                return $locked ?? $match;
            }

            $this->touch($locked, $user->id);

            $partnerSeen = $locked->user_one_id === $user->id
                ? $locked->user_two_seen_at
                : $locked->user_one_seen_at;

            if ($locked->user_two_id && $partnerSeen && $partnerSeen->lt(now()->subSeconds(self::CALL_STALE_SECONDS))) {
                $locked->status = VideoMatch::ENDED;
                $locked->end_reason = 'disconnect';
            }

            $locked->save();

            return $locked;
        });
    }

    private function endTokenMatch(User $user, string $token, string $reason): void
    {
        $match = $this->matchByToken($user, $token);

        if (! $match || $match->status === VideoMatch::ENDED) {
            return;
        }

        DB::transaction(function () use ($match, $reason, $user) {
            $locked = VideoMatch::query()->whereKey($match->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === VideoMatch::ENDED) {
                return;
            }

            $locked->status = VideoMatch::ENDED;
            $locked->end_reason = $reason;
            $locked->ended_at = now();
            $locked->save();
            $type = match ($reason) {
                'next' => MatchEvent::NEXT,
                'end' => MatchEvent::ENDED,
                default => MatchEvent::DISCONNECT,
            };
            MatchEvent::write($locked->id, $user->id, $type);
            app(AnalyticsRecorder::class)->track($user, $reason === 'next' ? 'next' : 'call_ended');
            if ($reason === 'next') {
                app(AnalyticsRecorder::class)->track($user, 'next_clicked');
            }
            $seconds = $locked->created_at ? (int) $locked->created_at->diffInSeconds($locked->ended_at) : 0;
            app(AnalyticsRecorder::class)->track($user, 'call_duration', ['seconds' => $seconds]);
        });
    }

    private function matchByToken(User $user, string $token): ?VideoMatch
    {
        return VideoMatch::query()
            ->forUser($user->id)
            ->where(function ($query) use ($user, $token) {
                $query->where(function ($query) use ($user, $token) {
                    $query->where('user_one_id', $user->id)->where('user_one_token', $token);
                })->orWhere(function ($query) use ($user, $token) {
                    $query->where('user_two_id', $user->id)->where('user_two_token', $token);
                });
            })
            ->latest('id')
            ->first();
    }

    private function activeFor(User $user): ?VideoMatch
    {
        return VideoMatch::query()->active()->forUser($user->id)->latest('id')->first();
    }

    private function expireAbandoned(): void
    {
        $queueCutoff = now()->subSeconds(self::QUEUE_STALE_SECONDS);
        $callCutoff = now()->subSeconds(self::CALL_STALE_SECONDS);

        VideoMatch::query()
            ->where('status', VideoMatch::WAITING)
            ->where('user_one_seen_at', '<', $queueCutoff)
            ->update([
                'status' => VideoMatch::ENDED,
                'end_reason' => 'disconnect',
                'updated_at' => now(),
            ]);

        VideoMatch::query()
            ->whereIn('status', [VideoMatch::CONNECTING, VideoMatch::CONNECTED])
            ->where('user_one_seen_at', '<', $callCutoff)
            ->where('user_two_seen_at', '<', $callCutoff)
            ->update([
                'status' => VideoMatch::ENDED,
                'end_reason' => 'disconnect',
                'updated_at' => now(),
            ]);
    }

    private function touch(VideoMatch $match, int $userId): void
    {
        if ($match->user_one_id === $userId) {
            $match->user_one_seen_at = now();
        } elseif ($match->user_two_id === $userId) {
            $match->user_two_seen_at = now();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(VideoMatch $match, User $user, int $cursor): array
    {
        $signal = $match->signal ?? [];
        $candidates = $signal['candidates'] ?? [];
        $outgoing = [];
        $maxId = $cursor;
        $paired = $match->user_two_id !== null && $match->status !== VideoMatch::ENDED;
        $role = null;

        if ($paired) {
            $role = $match->user_one_id === $user->id ? 'offerer' : 'answerer';
        }

        foreach ($candidates as $item) {
            $id = (int) ($item['id'] ?? 0);
            $maxId = max($maxId, $id);

            if ($paired && $id > $cursor && (int) ($item['user_id'] ?? 0) !== $user->id) {
                $outgoing[] = $item['candidate'];
            }
        }

        $partner = null;
        if ($paired) {
            $partnerId = $match->user_one_id === $user->id ? $match->user_two_id : $match->user_one_id;
            $partnerUser = User::query()->find($partnerId, ['id', 'name', 'country_code']);
            if ($partnerUser) {
                $partner = [
                    'name' => $partnerUser->name,
                    'country' => Countries::valid($partnerUser->country_code) ? Countries::label($partnerUser->country_code) : '',
                ];
            }
        }

        return [
            'status' => $match->status,
            'role' => $role,
            'end_reason' => $match->end_reason,
            'partner' => $partner,
            'offer' => $role === 'answerer' ? ($signal['offer'] ?? null) : null,
            'answer' => $role === 'offerer' ? ($signal['answer'] ?? null) : null,
            'candidates' => $outgoing,
            'cursor' => $maxId,
            'v' => (int) ($signal['v'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{type: string, sdp: string}
     */
    private function sessionDescription(array $payload, string $expectedType): array
    {
        $type = $payload['type'] ?? '';
        $sdp = $payload['sdp'] ?? '';

        if ($type !== $expectedType || ! is_string($sdp) || $sdp === '' || strlen($sdp) > 100000) {
            throw ValidationException::withMessages([
                'payload' => 'Invalid session description.',
            ]);
        }

        return ['type' => $expectedType, 'sdp' => $sdp];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function iceCandidate(array $payload): array
    {
        $candidate = $payload['candidate'] ?? '';

        if (! is_string($candidate) || $candidate === '' || strlen($candidate) > 2000) {
            throw ValidationException::withMessages([
                'payload' => 'Invalid candidate.',
            ]);
        }

        return [
            'candidate' => $candidate,
            'sdpMid' => isset($payload['sdpMid']) ? (string) $payload['sdpMid'] : null,
            'sdpMLineIndex' => isset($payload['sdpMLineIndex']) ? (int) $payload['sdpMLineIndex'] : null,
            'usernameFragment' => isset($payload['usernameFragment']) ? (string) $payload['usernameFragment'] : null,
        ];
    }

    private function token(Request $request): string
    {
        return $request->validate([
            'token' => ['required', 'string', 'max:64'],
        ])['token'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store');
    }

    private function denied(User $user): ?JsonResponse
    {
        if ($user->isBanned()) {
            return $this->json(['message' => 'Your account is suspended.'], 403);
        }

        return null;
    }

    private function afterSignal(VideoMatch $match, User $user, string $type): void
    {
        if ($type === 'offer') {
            MatchEvent::write($match->id, $user->id, MatchEvent::STARTED);
            app(AnalyticsRecorder::class)->track($user, 'connection_started');
        } elseif ($type === 'connected') {
            MatchEvent::write($match->id, $user->id, MatchEvent::CONNECTED);
            app(AnalyticsRecorder::class)->track($user, 'connection_connected');
            app(AnalyticsRecorder::class)->track($user, 'call_connected');
        } elseif ($type === 'failed') {
            MatchEvent::write($match->id, $user->id, MatchEvent::FAILED);
            app(AnalyticsRecorder::class)->track($user, 'connection_failed');
        }

        app(PresenceService::class)->touch($user->id, 'in_call');
        $this->broadcast($match);
    }

    private function broadcast(VideoMatch $match): void
    {
        try {
            event(new MatchSignal(array_filter([$match->user_one_token, $match->user_two_token]), $match->status));
        } catch (\Throwable) {
            // Polling remains available when the websocket server is down.
        }
    }
}
