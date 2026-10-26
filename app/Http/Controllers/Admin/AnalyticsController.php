<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMetric;
use App\Services\AnalyticsRollup;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    private const SECTIONS = ['overview', 'acquisition', 'funnel', 'calls', 'geography', 'devices', 'content', 'moderation'];

    public function show(Request $request, AnalyticsRollup $rollup, ?string $section = 'overview'): View
    {
        $section = in_array($section, self::SECTIONS, true) ? $section : 'overview';
        [$from, $to, $rows] = $this->rows($request, $rollup);

        $cards = $this->cards($section, $rows);
        $days = max(1, $from->diffInDays($to) + 1);
        $previousRows = DailyMetric::query()->whereBetween('date', [$from->copy()->subDays($days)->toDateString(), $from->copy()->subDay()->toDateString()])->get();

        return view('admin.analytics', [
            'section' => $section,
            'sections' => self::SECTIONS,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'cards' => $cards,
            'previous' => $this->cards($section, $previousRows),
            'table' => $this->table($section, $rows),
            'series' => $rows->where('metric', 'visits')->where('dimension', '')->sortBy('date')->map(fn ($row) => ['label' => $row->date->toDateString(), 'value' => (int) $row->value])->values(),
        ]);
    }

    public function export(Request $request, AnalyticsRollup $rollup, string $section): StreamedResponse
    {
        $section = in_array($section, self::SECTIONS, true) ? $section : 'overview';
        [, , $rows] = $this->rows($request, $rollup);
        $table = $this->table($section, $rows);

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
