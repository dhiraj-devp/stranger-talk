<?php

namespace App\Http\Controllers;

use App\Events\MatchSignal;
use App\Models\MatchEvent;
use App\Models\User;
use App\Models\VideoMatch;
use App\Services\AnalyticsRecorder;
use App\Services\MatchmakingService;
use App\Services\PresenceService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchController extends Controller
{
    private const STALE_SECONDS = 12;

    private const MAX_CANDIDATES = 300;

    public function find(Request $request): JsonResponse
    {
        if ($denied = $this->denied($request->user())) {
            return $denied;
        }

        $token = $this->token($request);
        $match = $this->enqueue($request->user(), $token);
        app(AnalyticsRecorder::class)->track($request->user(), 'find_stranger');

        return $this->json($this->present($match, $request->user(), 0));
    }

    public function poll(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $user = $request->user();
        $cursor = max(0, (int) $request->query('cursor', 0));
        $match = $this->matchByToken($user, $token);

        if (! $match) {
            $status = $this->activeFor($user) ? 'replaced' : 'idle';

            return $this->json(['status' => $status]);
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

        DB::transaction(function () use ($match, $user, $type, $payload, $isOfferer) {
            $locked = VideoMatch::query()->whereKey($match->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === VideoMatch::ENDED || $locked->user_two_id === null) {
                abort(409, 'Match is no longer active.');
            }

            $signal = $locked->signal ?? ['offer' => null, 'answer' => null, 'candidates' => []];

            if ($type === 'offer') {
                $signal['offer'] = $signal['offer'] ?? $this->sessionDescription($payload, 'offer');
            } elseif ($type === 'answer') {
                $signal['answer'] = $signal['answer'] ?? $this->sessionDescription($payload, 'answer');
            } elseif ($type === 'candidate') {
                $candidates = $signal['candidates'] ?? [];

                if (count($candidates) < self::MAX_CANDIDATES) {
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
            $this->afterSignal($locked, $user, $type);
        });

        return $this->json(['ok' => true]);
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

        $match = $this->withMatchLock(function () use ($user, $data) {
            $this->endTokenMatch($user, $data['token'], 'next');
            $this->expireAbandoned();

            return $this->pairOrQueue($user, $data['next_token']);
        });

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
        return $this->withMatchLock(function () use ($user, $token) {
            $this->expireAbandoned();

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

            return $this->pairOrQueue($user, $token);
        });
    }

    private function pairOrQueue(User $user, string $token): VideoMatch
    {
        $matchmaking = app(MatchmakingService::class);
        $waiters = VideoMatch::query()
            ->where('status', VideoMatch::WAITING)
            ->whereNull('user_two_id')
            ->where('user_one_id', '!=', $user->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->limit(25)
            ->get();

        foreach ($waiters as $waiting) {
            $partner = User::query()->find($waiting->user_one_id);
            if (! $matchmaking->canPair($user, $partner)) {
                continue;
            }

            $waiting->user_two_id = $user->id;
            $waiting->user_two_token = $token;
            $waiting->user_two_seen_at = now();
            $waiting->status = VideoMatch::CONNECTING;
            $waiting->save();
            User::query()->whereKey([$user->id, $waiting->user_one_id])->update(['status' => User::IN_CALL, 'last_seen_at' => now()]);
            MatchEvent::write($waiting->id, $user->id, MatchEvent::CREATED);
            app(PresenceService::class)->touch($user->id, 'in_call');
            app(AnalyticsRecorder::class)->track($user, 'match_created');
            $this->broadcast($waiting);

            return $waiting;
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

            if ($locked->user_two_id && $partnerSeen && $partnerSeen->lt(now()->subSeconds(self::STALE_SECONDS))) {
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
            $locked->save();
            $type = match ($reason) {
                'next' => MatchEvent::NEXT,
                'end' => MatchEvent::ENDED,
                default => MatchEvent::DISCONNECT,
            };
            MatchEvent::write($locked->id, $user->id, $type);
            app(AnalyticsRecorder::class)->track($user, $reason === 'next' ? 'next' : 'call_ended');
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
        $cutoff = now()->subSeconds(self::STALE_SECONDS);

        VideoMatch::query()
            ->where('status', VideoMatch::WAITING)
            ->where('user_one_seen_at', '<', $cutoff)
            ->update([
                'status' => VideoMatch::ENDED,
                'end_reason' => 'disconnect',
                'updated_at' => now(),
            ]);

        VideoMatch::query()
            ->whereIn('status', [VideoMatch::CONNECTING, VideoMatch::CONNECTED])
            ->where('user_one_seen_at', '<', $cutoff)
            ->where('user_two_seen_at', '<', $cutoff)
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
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    private function withMatchLock(callable $callback): mixed
    {
        try {
            return Cache::lock('stranger-matchmaking', 8)->block(5, function () use ($callback) {
                return DB::transaction($callback);
            });
        } catch (LockTimeoutException) {
            abort(503, 'Matching is busy. Please try again.');
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

        return [
            'status' => $match->status,
            'role' => $role,
            'end_reason' => $match->end_reason,
            'offer' => $role === 'answerer' ? ($signal['offer'] ?? null) : null,
            'answer' => $role === 'offerer' ? ($signal['answer'] ?? null) : null,
            'candidates' => $outgoing,
            'cursor' => $maxId,
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
