@extends('layouts.admin')

@section('content')
    <h1>Bans</h1>
    <section class="panel">
        <table>
            <thead><tr><th>User</th><th>Reason</th><th>Until</th><th>By</th><th>State</th></tr></thead>
            <tbody>
            @forelse ($bans as $ban)
                <tr>
                    <td>@if ($ban->user)<a href="{{ route('admin.users.show', $ban->user) }}">{{ $ban->user->name }}</a>@else Removed @endif</td>
                    <td>{{ $ban->reason }}</td>
                    <td>{{ $ban->permanent ? 'Permanent' : ($ban->banned_until?->toDayDateTimeString() ?: '—') }}</td>
                    <td>{{ $ban->admin->name ?? '—' }}</td>
                    <td><span @class(['badge', 'ok' => $ban->lifted_at, 'bad' => ! $ban->lifted_at])>{{ $ban->lifted_at ? 'Lifted' : 'Active' }}</span></td>
                </tr>
            @empty
                <tr><td class="empty">No bans recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pager">{{ $bans->links() }}</div>
    </section>
@endsection
