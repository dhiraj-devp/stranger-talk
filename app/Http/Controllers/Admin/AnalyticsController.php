<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMetric;
use App\Models\PageView;
use App\Models\Visit;
use App\Services\AnalyticsRollup;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    private const SECTIONS = ['overview', 'traffic', 'acquisition', 'funnel', 'calls', 'geography', 'devices', 'content', 'moderation'];

    public function show(Request $request, AnalyticsRollup $rollup, ?string $section = 'overview'): View
    {
        $section = in_array($section, self::SECTIONS, true) ? $section : 'overview';
        [$from, $to, $rows] = $this->rows($request, $rollup);

        $traffic = $section === 'traffic' ? $this->traffic($from, $to) : null;
        if ($traffic) {
            $cards = [
                'Page views' => $traffic['views'],
                'Unique addresses' => $traffic['uniques'],
                'Cookie visits' => $traffic['visits'],
            ];
            $previous = [];
            $table = array_merge(
                array_map(fn (array $day) => ['label' => $day['label'].' unique '.$day['uniques'], 'value' => $day['value']], $traffic['days']),
                array_map(fn (array $hour) => ['label' => $traffic['day'].' '.$hour['label'].' unique '.$hour['uniques'], 'value' => $hour['views']], $traffic['hours'])
            );
            $series = collect($traffic['days']);
        } else {
            $cards = $this->cards($section, $rows);
            $days = max(1, $from->diffInDays($to) + 1);
            $previousRows = DailyMetric::query()->whereBetween('date', [$from->copy()->subDays($days)->toDateString(), $from->copy()->subDay()->toDateString()])->get();
            $previous = $this->cards($section, $previousRows);
            $table = $this->table($section, $rows);
            $series = $rows->where('metric', 'visits')->where('dimension', '')->sortBy('date')->map(fn ($row) => ['label' => $row->date->toDateString(), 'value' => (int) $row->value])->values();
        }

        return view('admin.analytics', [
            'section' => $section,
            'sections' => self::SECTIONS,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'cards' => $cards,
            'previous' => $previous,
            'table' => $table,
            'series' => $series,
            'traffic' => $traffic,
        ]);
    }

    public function export(Request $request, AnalyticsRollup $rollup, string $section): StreamedResponse
    {
        $section = in_array($section, self::SECTIONS, true) ? $section : 'overview';
        [$from, $to, $rows] = $this->rows($request, $rollup);
        if ($section === 'traffic') {
            $traffic = $this->traffic($from, $to);
            $table = array_merge(
                array_map(fn (array $day) => ['label' => $day['label'].' unique '.$day['uniques'], 'value' => $day['value']], $traffic['days']),
                array_map(fn (array $hour) => ['label' => $traffic['day'].' '.$hour['label'].' unique '.$hour['uniques'], 'value' => $hour['views']], $traffic['hours'])
            );
        } else {
            $table = $this->table($section, $rows);
        }

        return response()->streamDownload(function () use ($table) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Label', 'Value']);
            foreach ($table as $row) {
                fputcsv($out, [$row['label'], $row['value']]);
            }
            fclose($out);
        }, $section.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function rows(Request $request, AnalyticsRollup $rollup): array
    {
        $to = $request->date('to') ?: now();
        $from = $request->date('from') ?: $to->copy()->subDays(6);
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }
        try {
            if ($from->diffInDays($to) <= 14) {
                for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
                    $rollup->day($cursor);
                }
            } else {
                $rollup->day(now());
            }
            $rows = DailyMetric::query()
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->get();
        } catch (\Throwable) {
            $rows = collect();
        }

        return [$from, $to, $rows];
    }

    private function cards(string $section, $rows): array
    {
        $sum = fn (string $metric, string $dimension = '') => (int) $rows->where('metric', $metric)->when($dimension !== '', fn ($set) => $set->where('dimension', $dimension))->sum('value');
        $event = fn (string $name) => $sum('event', $name);

        return match ($section) {
            'acquisition' => ['Visits' => $sum('visits'), 'Referrers' => $sum('referrer')],
            'funnel' => [
                'Landing views' => $sum('path_views', '/'),
                'Sign-ins' => $event('login'),
                'Profiles' => $event('profile_completed'),
                'Searches' => $event('search_started'),
                'Connected' => $event('call_connected'),
                'Next' => $event('next'),
                'Reports' => $event('report'),
            ],
            'calls' => [
                'Connected' => $event('call_connected'),
                'Next' => $event('next'),
                'Ended' => $event('call_ended'),
                'Duration events' => $event('call_duration'),
            ],
            'geography' => ['Visits' => $sum('visits')],
            'devices' => ['Visits' => $sum('visits')],
            'content' => ['Page views' => $sum('page_views'), 'Visible seconds' => $sum('duration')],
            'moderation' => ['Reports' => $event('report'), 'Blocks' => $event('block')],
            default => [
                'Visits' => $sum('visits'),
                'Page views' => $sum('page_views'),
                'Visible seconds' => $sum('duration'),
                'DAU' => $sum('dau'),
            ],
        };
    }

    private function traffic($from, $to): array
    {
        $from = $from->copy()->startOfDay();
        $toEnd = $to->copy()->endOfDay();
        if ($from->diffInDays($toEnd) > 31) {
            $from = $toEnd->copy()->subDays(30)->startOfDay();
        }

        $pageViews = PageView::query()
            ->with('visit:id,ip_address')
            ->whereBetween('entered_at', [$from, $toEnd])
            ->get(['id', 'visit_id', 'entered_at']);

        $days = [];
        for ($cursor = $from->copy(); $cursor->lte($toEnd); $cursor->addDay()) {
            $days[$cursor->toDateString()] = ['label' => $cursor->toDateString(), 'value' => 0, 'ips' => []];
        }
        $hours = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[$hour] = ['label' => sprintf('%02d:00', $hour), 'views' => 0, 'ips' => []];
        }

        $reportDay = $to->copy()->startOfDay()->toDateString();
        $unique = [];
        foreach ($pageViews as $view) {
            $at = $view->entered_at;
            if (! $at) {
                continue;
            }
            $day = $at->toDateString();
            if (! isset($days[$day])) {
                continue;
            }
            $days[$day]['value']++;
            $ip = $view->visit->ip_address ?? null;
            if (is_string($ip) && $ip !== '') {
                $days[$day]['ips'][$ip] = true;
                $unique[$ip] = true;
            }
            if ($day === $reportDay) {
                $hour = (int) $at->format('G');
                $hours[$hour]['views']++;
                if (is_string($ip) && $ip !== '') {
                    $hours[$hour]['ips'][$ip] = true;
                }
            }
        }

        return [
            'views' => $pageViews->count(),
            'uniques' => count($unique),
            'visits' => Visit::query()->whereBetween('started_at', [$from, $toEnd])->count(),
            'day' => $reportDay,
            'days' => array_map(fn (array $day) => ['label' => $day['label'], 'value' => $day['value'], 'uniques' => count($day['ips'])], array_values($days)),
            'hours' => array_map(fn (array $hour) => ['label' => $hour['label'], 'views' => $hour['views'], 'uniques' => count($hour['ips'])], $hours),
        ];
    }

    private function table(string $section, $rows): array
    {
        $metric = match ($section) {
            'acquisition' => 'referrer',
            'geography' => 'country',
            'devices' => 'device',
            'content' => 'path_views',
            'funnel', 'calls', 'moderation' => 'event',
            default => 'path_views',
        };
        $wanted = match ($section) {
            'funnel' => ['login', 'signup_completed', 'profile_completed', 'search_started', 'call_connected', 'next', 'report'],
            'calls' => ['call_connected', 'call_duration', 'next', 'call_ended'],
            'moderation' => ['report', 'block'],
            default => null,
        };

        return $rows->where('metric', $metric)
            ->when($wanted, fn ($set) => $set->whereIn('dimension', $wanted))
            ->groupBy('dimension')
            ->map(fn ($group, $dimension) => [
                'label' => $dimension === '' ? 'Direct / unknown' : (string) $dimension,
                'value' => (int) $group->sum('value'),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }
}
