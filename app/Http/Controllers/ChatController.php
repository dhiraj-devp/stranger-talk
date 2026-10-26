<?php

namespace App\Http\Controllers;

use App\Models\MatchMessage;
use App\Models\User;
use App\Models\VideoMatch;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $match = $this->matchFor($request->user(), $token);
        $this->ensureOpen($match);
        $after = max(0, (int) $request->query('after', 0));

        $messages = MatchMessage::query()
            ->where('match_id', $match->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(50)
            ->get();

        return response()->json([
            'messages' => $messages->map(fn (MatchMessage $message) => $this->present($message, $request->user()))->values(),
            'cursor' => (int) ($messages->last()->id ?? $after),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'client_id' => ['required', 'string', 'max:64'],
            'body' => ['required', 'string', 'max:500'],
        ]);
        $body = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', str_replace("\r\n", "\n", $data['body'])) ?? '');
        if ($body === '') {
            return response()->json(['message' => 'Enter a message.'], 422);
        }

        $match = $this->matchFor($request->user(), $data['token']);
        $this->ensureOpen($match);

        $existing = MatchMessage::query()
            ->where('match_id', $match->id)
            ->where('client_id', $data['client_id'])
            ->first();
        if ($existing) {
            abort_if($existing->sender_id !== $request->user()->id, 403);

            return response()->json(['message' => $this->present($existing, $request->user())]);
        }

        try {
            $message = MatchMessage::query()->create([
                'match_id' => $match->id,
                'sender_id' => $request->user()->id,
                'client_id' => $data['client_id'],
                'body' => $body,
            ]);
        } catch (QueryException) {
            $message = MatchMessage::query()
                ->where('match_id', $match->id)
                ->where('client_id', $data['client_id'])
                ->first();
            abort_if(! $message || $message->sender_id !== $request->user()->id, 403);

            return response()->json(['message' => $this->present($message, $request->user())]);
        }

        return response()->json(['message' => $this->present($message, $request->user())], 201);
    }

    private function token(Request $request): string
    {
        return $request->validate([
            'token' => ['required', 'string', 'max:64'],
        ])['token'];
    }

    private function matchFor(User $user, string $token): VideoMatch
    {
        $match = VideoMatch::query()
            ->where(function ($query) use ($user, $token) {
                $query->where(fn ($query) => $query->where('user_one_id', $user->id)->where('user_one_token', $token))
                    ->orWhere(fn ($query) => $query->where('user_two_id', $user->id)->where('user_two_token', $token));
            })
            ->first();

        abort_if(! $match, 404);

        return $match;
    }

    private function ensureOpen(VideoMatch $match): void
    {
        abort_unless(
            in_array($match->status, [VideoMatch::CONNECTING, VideoMatch::CONNECTED], true) && $match->user_two_id,
            409,
            'Chat is available only during this match.'
        );
    }

    /**
     * @return array{id: int, client_id: string, body: string, mine: bool, at: string}
     */
    private function present(MatchMessage $message, User $user): array
    {
        return [
            'id' => $message->id,
            'client_id' => $message->client_id,
            'body' => $message->body,
            'mine' => $message->sender_id === $user->id,
            'at' => $message->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }
}
