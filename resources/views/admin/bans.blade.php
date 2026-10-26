@extends('layouts.admin')

@section('crumb', 'Bans')

@section('content')
    <h1>Bans</h1>
    <form class="filters" method="GET">
        <label>Search <input name="q" value="{{ $q }}" placeholder="Name or email"></label>
        <label>State
            <select name="state">
                <option value="">Any</option>
                @foreach (['active' => 'Active', 'expired' => 'Expired', 'permanent' => 'Permanent', 'lifted' => 'Lifted'] as $value => $label)
                    <option value="{{ $value }}" @selected($state === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Filter</button>
    </form>
    <section class="panel">
        <table>
            <thead><tr><th>User</th><th>Reason</th><th>Until</th><th>Remaining</th><th>By</th><th>State</th></tr></thead>
            <tbody>
            @forelse ($bans as $ban)
                <tr>
                    <td>@if ($ban->user)<a href="{{ route('admin.users.show', $ban->user) }}">{{ $ban->user->name }}</a>@else Removed @endif</td>
                    <td>{{ $ban->reason }}</td>
                    <td>{{ $ban->permanent ? 'Permanent' : ($ban->banned_until?->toDayDateTimeString() ?: '—') }}</td>
                    <td>{{ $ban->lifted_at ? '—' : ($ban->permanent ? 'Permanent' : ($ban->banned_until?->isFuture() ? $ban->banned_until->diffForHumans() : 'Expired')) }}</td>
                    <td>{{ $ban->admin->name ?? '—' }}</td>
                    <td>
                        <span @class(['badge', 'ok' => $ban->lifted_at, 'bad' => ! $ban->lifted_at])>{{ $ban->lifted_at ? 'Lifted' : 'Active' }}</span>
                        @if (! $ban->lifted_at)
                            <form method="POST" action="{{ route('admin.bans.revise', $ban) }}" data-confirm="Change this ban?">
                                @csrf
                                <input name="hours" type="number" min="1" value="24" aria-label="Hours remaining">
                                <button type="submit">Update time</button>
                                <button type="submit" name="lift" value="1" class="secondary">Lift</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td class="empty">No bans recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pager">{{ $bans->links() }}</div>
    </section>
@endsection
