@extends('layouts.admin')

@section('content')
    <h1>Audit</h1>
    <p class="lead">Sensitive admin actions. Passwords and secrets are not logged.</p>
    <section class="panel">
        <table>
            <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->admin->name ?? '—' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->target_type }} {{ $log->target_id }}</td>
                    <td>{{ $log->ip_address ?: '—' }}</td>
                </tr>
            @empty
                <tr><td class="empty">No audit entries.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pager">{{ $logs->links() }}</div>
    </section>
@endsection
