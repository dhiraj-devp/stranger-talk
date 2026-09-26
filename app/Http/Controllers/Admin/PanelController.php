<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\AuditLog;
use App\Models\MatchEvent;
use App\Models\Report;
use App\Models\User;
use App\Models\VideoMatch;
use App\Services\SafetyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function dashboard(): View
    {
        $stats = Cache::remember('admin.dashboard', 60, fn () => $this->stats());

        return view('admin.dashboard', ['stats' => $stats]);
    }

    public function users(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $users = User::query()
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'q' => $q]);
    }

    public function showUser(User $user): View
    {
        return view('admin.user', [
            'user' => $user,
            'reports' => Report::query()->where('reported_user_id', $user->id)->latest()->limit(20)->get(),
            'matches' => VideoMatch::query()->forUser($user->id)->latest()->limit(20)->get(),
            'audit' => AuditLog::query()->where('target_type', 'User')->where('target_id', $user->id)->latest('id')->limit(20)->get(),
        ]);
    }

    public function ban(Request $request, User $user, SafetyService $safety): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'permanent' => ['nullable', 'boolean'],
        ]);

        $until = $request->boolean('permanent') ? null : now()->addHours((int) ($data['hours'] ?? 24));
        $safety->ban($user, $request->user(), $data['reason'], $until, $data['notes'] ?? null, $request->ip());

        return back()->with('status', 'User banned.');
    }

    public function unban(Request $request, User $user, SafetyService $safety): RedirectResponse
    {
        $safety->unban($user, $request->user(), $request->ip());

        return back()->with('status', 'User unbanned.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['confirm_email' => ['required', 'email']]);
        if ($user->id === $request->user()->id || strcasecmp($data['confirm_email'], $user->email) !== 0) {
            return back()->withErrors(['confirm_email' => 'Type the user email to confirm deletion.']);
        }

        \App\Models\AuditLog::write($request->user(), 'delete_user', $user, [], $request->ip());
        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('admin.users')->with('status', 'User deleted.');
    }

    public function reports(Request $request): View
    {
        $status = $request->query('status', Report::PENDING);
        $reports = Report::query()->with(['reporter', 'reported'])->where('status', $status)->latest()->paginate(20);

        return view('admin.reports', ['reports' => $reports, 'status' => $status]);
    }

    public function updateReport(Request $request, Report $report): RedirectResponse
    {
        $this->authorize('update', $report);
        $data = $request->validate([
            'status' => ['required', 'in:pending,reviewing,resolved,rejected'],
            'moderator_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $report->fill([
            'status' => $data['status'],
            'moderator_notes' => $data['moderator_notes'] ?? $report->moderator_notes,
            'moderator_id' => $request->user()->id,
        ])->save();

        $action = $data['status'] === Report::RESOLVED ? 'resolve_report' : ($data['status'] === Report::REJECTED ? 'reject_report' : 'review_report');
        \App\Models\AuditLog::write($request->user(), $action, $report, ['status' => $data['status']], $request->ip());

        return back()->with('status', 'Report updated.');
    }

    public function health(): View
    {
        return view('admin.health', ['health' => app(\App\Http\Controllers\HealthController::class)()->getData(true)]);
    }

    private function stats(): array
    {
        $connected = MatchEvent::query()->where('type', MatchEvent::CONNECTED);
        $failed = MatchEvent::query()->where('type', MatchEvent::FAILED);
        $samples = VideoMatch::query()->where('status', VideoMatch::ENDED)->latest()->limit(100)->get(['created_at', 'updated_at']);
        $avg = $samples->isEmpty() ? 0 : (int) round($samples->avg(fn (VideoMatch $match) => $match->created_at && $match->updated_at ? $match->updated_at->diffInSeconds($match->created_at) : 0));

        return [
            'users' => User::query()->count(),
            'online' => User::query()->where('last_seen_at', '>=', now()->subMinutes(2))->count(),
            'searching' => User::query()->where('status', User::SEARCHING)->count(),
            'active_calls' => VideoMatch::query()->whereIn('status', [VideoMatch::CONNECTING, VideoMatch::CONNECTED])->count(),
            'calls_today' => VideoMatch::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'successful' => (clone $connected)->count(),
            'failed' => (clone $failed)->count(),
            'avg_seconds' => $avg,
            'pending_reports' => Report::query()->where('status', Report::PENDING)->count(),
            'active_bans' => User::query()->where('status', User::BANNED)->count(),
            'dau' => AnalyticsEvent::query()->where('created_at', '>=', now()->subDay())->distinct()->count('user_id'),
            'mau' => AnalyticsEvent::query()->where('created_at', '>=', now()->subDays(30))->distinct()->count('user_id'),
            'registrations' => AnalyticsEvent::query()->where('name', 'registration')->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }
}
