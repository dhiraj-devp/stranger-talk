<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\DailyMetric;
use App\Models\PageView;
use App\Models\Visit;
use Illuminate\Support\Carbon;

class AnalyticsRollup
{
    public function day(Carbon $date): void
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $rows = [];

        $rows[] = ['visits', '', Visit::query()->whereBetween('started_at', [$start, $end])->count()];
        $rows[] = ['page_views', '', PageView::query()->whereBetween('entered_at', [$start, $end])->count()];
        $rows[] = ['duration', '', (int) PageView::query()->whereBetween('entered_at', [$start, $end])->sum('duration')];

        PageView::query()->whereBetween('entered_at', [$start, $end])
            ->selectRaw('path as dimension, count(*) as value')
            ->groupBy('path')
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[] = ['path_views', (string) $row->dimension, (int) $row->value];
            });

        Visit::query()->whereBetween('started_at', [$start, $end])
            ->selectRaw("coalesce(referrer_host, '') as dimension, count(*) as value")
            ->groupByRaw("coalesce(referrer_host, '')")
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[] = ['referrer', (string) $row->dimension, (int) $row->value];
            });

        Visit::query()->whereBetween('started_at', [$start, $end])
            ->selectRaw("coalesce(country, '') as dimension, count(*) as value")
            ->groupByRaw("coalesce(country, '')")
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[] = ['country', (string) $row->dimension, (int) $row->value];
            });

        Visit::query()->whereBetween('started_at', [$start, $end])
            ->selectRaw('device as dimension, count(*) as value')
            ->groupBy('device')
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[] = ['device', (string) $row->dimension, (int) $row->value];
            });

        AnalyticsEvent::query()->whereBetween('created_at', [$start, $end])
            ->selectRaw('name as dimension, count(*) as value')
            ->groupBy('name')
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[] = ['event', (string) $row->dimension, (int) $row->value];
            });

        $rows[] = ['dau', '', AnalyticsEvent::query()->whereBetween('created_at', [$start, $end])->distinct()->count('user_id')];

        foreach ($rows as [$metric, $dimension, $value]) {
            DailyMetric::query()->updateOrCreate(
                ['date' => $start->toDateString(), 'metric' => $metric, 'dimension' => mb_substr($dimension, 0, 120)],
                ['value' => $value]
            );
        }
    }
}
