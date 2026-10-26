@extends('layouts.admin')

@section('crumb', 'Overview')

@section('content')
    <h1>Overview</h1>
    <p class="lead">Signed-in activity for {{ $brand->site_name }}. These counts are not anonymous visits. Numbers refresh about once a minute. @if (($stats['successful'] + $stats['failed']) > 0) Connection success is {{ (int) round($stats['successful'] / ($stats['successful'] + $stats['failed']) * 100) }}% of recorded connect and fail events. @else Connection success is not shown until connect or fail events exist. @endif</p>
    <section class="metrics">
        @foreach ([
            'Users' => $stats['users'],
            'Online' => $stats['online'],
            'Searching' => $stats['searching'],
            'Active calls' => $stats['active_calls'],
            'Calls today' => $stats['calls_today'],
            'Connected events' => $stats['successful'],
            'Failed events' => $stats['failed'],
            'Avg seconds' => $stats['avg_seconds'],
            'Pending reports' => $stats['pending_reports'],
            'Needs review' => $needsReview,
            'Active bans' => $stats['active_bans'],
            'DAU' => $stats['dau'],
            'MAU' => $stats['mau'],
        ] as $label => $value)
            <article><span>{{ $label }}</span><strong>{{ $value }}</strong></article>
        @endforeach
    </section>
    <section class="panel" style="margin-bottom:14px">
        <h2>Visits, last 7 days</h2>
        <p class="hint">Cookie visits from the daily rollup, including days with zero. Signed-in events stay in the counts above. Change the range in <a href="{{ route('admin.analytics') }}">Analytics</a>.</p>
        @php $max = max(1, $visitTrend->max('value')); @endphp
        <div class="bars" aria-label="Visits by day">
            @foreach ($visitTrend as $point)
                <div><b>{{ $point['label'] }}</b><span><i style="width: {{ (int) round($point['value'] / $max * 100) }}%"></i></span>{{ $point['value'] }}</div>
            @endforeach
        </div>
    </section>
    <section class="panel" style="margin-bottom:14px">
        <h2>Dependencies</h2>
        <p class="hint">Optional services are not an outage when video chat can still use polling.</p>
        @foreach ($health as $name => $value)
            <span @class(['badge', 'ok' => in_array($value, ['ok', 'configured'], true), 'warn' => in_array($value, ['disabled', 'not_configured'], true), 'bad' => $value === 'unavailable'])>{{ $name }}: {{ $value }}</span>
        @endforeach
    </section>
    <div class="split">
        <section class="panel">
            <h2>Latest reports</h2>
            <table>
                <tbody>
                @forelse ($recentReports as $report)
                    <tr>
                        <td><a href="{{ route('admin.reports', ['status' => $report->status]) }}">{{ $report->reason }}</a></td>
                        <td>{{ $report->reported->name ?? 'User' }}</td>
                        <td><span class="badge">{{ $report->status }}</span></td>
                    </tr>
                @empty
                    <tr><td class="empty">No reports yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <section class="panel">
            <h2>Latest matches</h2>
            <table>
                <tbody>
                @forelse ($recentMatches as $match)
                    <tr>
                        <td><a href="{{ route('admin.matches.show', $match) }}">#{{ $match->id }}</a></td>
                        <td>{{ $match->userOne->name ?? 'User' }} @if ($match->userTwo) / {{ $match->userTwo->name }} @endif</td>
                        <td><span class="badge">{{ $match->status }}</span></td>
                    </tr>
                @empty
                    <tr><td class="empty">No matches yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
    <section class="panel" style="margin-top:14px">
        <h2>Recent administrative actions</h2>
        <table>
            <tbody>
            @forelse ($recentAudit as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->admin->name ?? '—' }}</td>
                    <td>{{ $log->action }}</td>
                </tr>
            @empty
                <tr><td class="empty">No audit entries.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
