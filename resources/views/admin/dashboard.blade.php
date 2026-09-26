@extends('layouts.app')

@section('content')
    <section class="card wide">
        <h1>Admin</h1>
        <div class="stats">
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
                'Registrations 30d' => $stats['registrations'],
            ] as $label => $value)
                <p><span>{{ $label }}</span><strong>{{ $value }}</strong></p>
            @endforeach
        </div>
        <p class="switch">
            <a href="{{ route('admin.users') }}">Users</a>
            <a href="{{ route('admin.reports') }}">Reports</a>
            <a href="{{ route('admin.health') }}">Health</a>
            <a href="{{ route('home') }}">Home</a>
        </p>
    </section>
@endsection
