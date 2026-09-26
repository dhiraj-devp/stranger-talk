<?php

namespace App\Console\Commands;

use App\Models\AnalyticsEvent;
use App\Models\MatchEvent;
use App\Models\User;
use App\Models\VideoMatch;
use Illuminate\Console\Command;

class CleanupPlatform extends Command
{
    protected $signature = 'platform:cleanup';

    protected $description = 'Clear stale matches, expired bans, and old temporary analytics';

    public function handle(): int
    {
        $cutoff = now()->subSeconds((int) config('platform.stale_seconds', 30));

        VideoMatch::query()
            ->where('status', VideoMatch::WAITING)
            ->where('user_one_seen_at', '<', $cutoff)
            ->update(['status' => VideoMatch::EXPIRED, 'end_reason' => 'disconnect', 'updated_at' => now()]);

        VideoMatch::query()
            ->whereIn('status', [VideoMatch::CONNECTING, VideoMatch::CONNECTED])
            ->where('user_one_seen_at', '<', $cutoff)
            ->where('user_two_seen_at', '<', $cutoff)
            ->update(['status' => VideoMatch::EXPIRED, 'end_reason' => 'disconnect', 'updated_at' => now()]);

        User::query()
            ->where('status', User::BANNED)
            ->whereNotNull('banned_until')
            ->where('banned_until', '<', now())
            ->update(['status' => User::ACTIVE, 'banned_until' => null, 'ban_reason' => null]);

        AnalyticsEvent::query()
            ->where('created_at', '<', now()->subDays((int) config('platform.retention.analytics_days', 180)))
            ->delete();

        MatchEvent::query()
            ->where('created_at', '<', now()->subDays((int) config('platform.retention.match_events_days', 90)))
            ->delete();

        $this->info('Cleanup finished.');

        return self::SUCCESS;
    }
}
