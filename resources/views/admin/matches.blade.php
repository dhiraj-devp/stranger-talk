@extends('layouts.admin')

@section('crumb', 'Matches')

@section('content')
    <h1>Match history</h1>
    <p class="lead">Call records only. Media is not stored and calls cannot be watched from here.</p>
    <form class="filters" method="GET">
        <label>Status
            <select name="status">
                <option value="">Any</option>
                @foreach (['waiting', 'connecting', 'connected', 'ended', 'failed', 'expired'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <label>End reason
            <select name="end_reason">
                <option value="">Any</option>
                @foreach (['end', 'next', 'disconnect'] as $option)
                    <option value="{{ $option }}" @selected($reason === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <label>From <input type="date" name="from" value="{{ request('from') }}"></label>
        <label>To <input type="date" name="to" value="{{ request('to') }}"></label>
        <button type="submit">Filter</button>
    </form>
    <section class="panel">
        <table>
            <thead><tr><th>ID</th><th>People</th><th>Status</th><th>Ended</th><th>Started</th></tr></thead>
            <tbody>
            @forelse ($matches as $match)
                <tr>
                    <td><a href="{{ route('admin.matches.show', $match) }}">#{{ $match->id }}</a></td>
                    <td>{{ $match->userOne->name ?? 'User' }} @if ($match->userTwo) / {{ $match->userTwo->name }} @endif</td>
                    <td><span class="badge">{{ $match->status }}</span></td>
                    <td>{{ $match->end_reason ?: '—' }}</td>
                    <td>{{ $match->created_at?->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="5">No matches match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pager">{{ $matches->links() }}</div>
    </section>
@endsection
