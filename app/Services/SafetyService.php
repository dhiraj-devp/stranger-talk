<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ban;
use App\Models\Block;
use App\Models\Report;
use App\Models\User;
use App\Models\VideoMatch;
use Illuminate\Support\Carbon;

class SafetyService
{
    public function blockedIds(int $userId): array
    {
        $outgoing = Block::query()->where('blocker_id', $userId)->pluck('blocked_id');
        $incoming = Block::query()->where('blocked_id', $userId)->pluck('blocker_id');

        return $outgoing->merge($incoming)->unique()->map(fn ($id) => (int) $id)->all();
    }

    public function block(User $blocker, int $blockedId): Block
    {
        return Block::query()->firstOrCreate([
            'blocker_id' => $blocker->id,
            'blocked_id' => $blockedId,
        ]);
    }

    public function report(User $reporter, int $reportedId, ?int $matchId, string $reason, ?string $description): Report
    {
        $recent = Report::query()
            ->where('reporter_id', $reporter->id)
            ->where('reported_user_id', $reportedId)
            ->where('created_at', '>=', now()->subHour())
            ->first();
        if ($recent) {
            return $recent;
        }

        return Report::query()->create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reportedId,
            'match_id' => $matchId,
            'reason' => $reason,
            'description' => $description,
            'status' => Report::PENDING,
        ]);
    }

    public function ban(User $user, User $admin, string $reason, ?Carbon $until, ?string $notes, ?string $ip): void
    {
        $user->forceFill([
            'status' => User::BANNED,
            'banned_until' => $until,
            'ban_reason' => $reason,
        ])->save();

        Ban::query()->create([
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'reason' => $reason,
            'notes' => $notes,
            'permanent' => $until === null,
            'banned_until' => $until,
        ]);

        VideoMatch::query()->active()->forUser($user->id)->update([
            'status' => VideoMatch::ENDED,
            'end_reason' => 'end',
            'updated_at' => now(),
        ]);

        AuditLog::write($admin, 'ban', $user, [
            'permanent' => $until === null,
            'until' => $until?->toIso8601String(),
        ], $ip);
    }

    public function unban(User $user, User $admin, ?string $ip): void
    {
        $user->forceFill([
            'status' => User::ACTIVE,
            'banned_until' => null,
            'ban_reason' => null,
        ])->save();

        Ban::query()->where('user_id', $user->id)->whereNull('lifted_at')->update([
            'lifted_at' => now(),
        ]);

        AuditLog::write($admin, 'unban', $user, [], $ip);
    }
}
