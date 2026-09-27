@extends('layouts.admin')

@section('content')
    <h1>Overview</h1>
    <p class="lead">Live operations for StrangerTalk. Numbers refresh about once a minute.</p>
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
            'Active bans' => $stats['active_bans'],
            'DAU' => $stats['dau'],
            'MAU' => $stats['mau'],
        ] as $label => $value)
            <article><span>{{ $label }}</span><strong>{{ $value }}</strong></article>
        @endforeach
    </section>
    <div class="split">
        <section class="panel">
            <h2>Latest reports</h2>
            <table>
                <tbody>
                @forelse ($recentReports as $report)
                    <tr>
                        <td>{{ $report->reason }}</td>
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
                        <td>#{{ $match->id }}</td>
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
@endsection
