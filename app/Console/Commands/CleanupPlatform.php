<?php

namespace App\Console\Commands;

use App\Models\AnalyticsEvent;
use App\Models\DailyMetric;
use App\Models\MatchEvent;
use App\Models\PageView;
use App\Models\User;
use App\Models\VideoMatch;
use App\Models\Visit;
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

        $analyticsCutoff = now()->subDays((int) config('platform.retention.analytics_days', 180));
        PageView::query()->where('entered_at', '<', $analyticsCutoff)->delete();
        Visit::query()->where('started_at', '<', $analyticsCutoff)->delete();
        DailyMetric::query()->where('date', '<', $analyticsCutoff->toDateString())->delete();

        $this->info('Cleanup finished.');

        return self::SUCCESS;
    }
}
