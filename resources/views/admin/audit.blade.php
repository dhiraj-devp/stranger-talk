@extends('layouts.admin')

@section('crumb', 'Audit')

@section('content')
    <h1>Audit trail</h1>
    <p class="lead">Passwords, tokens, and secrets are not logged.</p>
    <form class="filters" method="GET">
        <label>Search <input name="q" value="{{ $q }}" placeholder="Action, target, or ID"></label>
        <label>Action <input name="action" value="{{ $action }}" placeholder="ban"></label>
        <label>From <input type="date" name="from" value="{{ request('from') }}"></label>
        <label>To <input type="date" name="to" value="{{ request('to') }}"></label>
        <button type="submit">Filter</button>
        <a class="btn" href="{{ route('admin.audit.export', request()->query()) }}">Export CSV</a>
    </form>
    <section class="panel">
        <table>
            <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Detail</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->admin->name ?? '—' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->target_type }} {{ $log->target_id }}</td>
                    <td>{{ $log->metadata ? json_encode($log->metadata) : '—' }}</td>
                    <td>{{ $log->ip_address ?: '—' }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="6">No audit entries.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pager">{{ $logs->links() }}</div>
    </section>
@endsection
