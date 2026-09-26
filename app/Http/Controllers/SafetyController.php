<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Report;
use App\Models\VideoMatch;
use App\Services\AnalyticsRecorder;
use App\Services\SafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SafetyController extends Controller
{
    public function report(Request $request, SafetyService $safety): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'reason' => ['required', 'in:'.implode(',', Report::REASONS)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $match = $this->matchFor($request, $data['token']);
        $partner = $this->partnerId($match, $request->user()->id);
        if (! $partner) {
            return response()->json(['message' => 'Nobody to report.'], 422);
        }

        $safety->report($request->user(), $partner, $match->id, $data['reason'], $data['description'] ?? null);
        app(AnalyticsRecorder::class)->track($request->user(), 'report');

        return response()->json(['ok' => true]);
    }

    public function block(Request $request, SafetyService $safety): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:64']]);
        $match = $this->matchFor($request, $data['token']);
        $partner = $this->partnerId($match, $request->user()->id);
        if (! $partner) {
            return response()->json(['message' => 'Nobody to block.'], 422);
        }

        $safety->block($request->user(), $partner);
        DB::transaction(function () use ($match) {
            $locked = VideoMatch::query()->whereKey($match->id)->lockForUpdate()->first();
            if ($locked && $locked->status !== VideoMatch::ENDED) {
                $locked->status = VideoMatch::ENDED;
                $locked->end_reason = 'end';
                $locked->save();
            }
        });
        app(AnalyticsRecorder::class)->track($request->user(), 'block');

        return response()->json(['ok' => true, 'status' => 'ended']);
    }

    public function index(Request $request): View
    {
        $blocks = Block::query()->where('blocker_id', $request->user()->id)->latest()->get();

        return view('blocks', ['blocks' => $blocks]);
    }

    public function unblock(Request $request, Block $block): RedirectResponse
    {
        $this->authorize('delete', $block);
        $block->delete();

        return back();
    }

    private function matchFor(Request $request, string $token): VideoMatch
    {
        $user = $request->user();
        $match = VideoMatch::query()
            ->forUser($user->id)
            ->where(function ($query) use ($user, $token) {
                $query->where(fn ($q) => $q->where('user_one_id', $user->id)->where('user_one_token', $token))
                    ->orWhere(fn ($q) => $q->where('user_two_id', $user->id)->where('user_two_token', $token));
            })
            ->latest('id')
            ->first();

        abort_unless($match, 404);

        return $match;
    }

    private function partnerId(VideoMatch $match, int $userId): ?int
    {
        if ($match->user_one_id === $userId) {
            return $match->user_two_id;
        }

        return $match->user_one_id;
    }
}
