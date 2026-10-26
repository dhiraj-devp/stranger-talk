<?php

namespace App\Console\Commands;

use App\Services\AnalyticsRollup;
use Illuminate\Console\Command;

class RollupAnalytics extends Command
{
    protected $signature = 'analytics:rollup {--date=}';

    protected $description = 'Roll page views and product events into daily metrics';

    public function handle(AnalyticsRollup $rollup): int
    {
        $date = $this->option('date') ? now()->parse($this->option('date')) : now();
        $rollup->day($date);
        $this->info('Rolled up '.$date->toDateString());

        return self::SUCCESS;
    }
}
