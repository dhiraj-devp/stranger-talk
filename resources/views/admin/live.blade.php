@extends('layouts.admin')

@section('content')
    <h1>Live</h1>
    <p class="lead">People searching and calls in progress. No video or audio is shown.</p>
    <div class="split">
        <section class="panel">
            <h2>Searching</h2>
            <table>
                <tbody>
                @forelse ($searching as $user)
                    <tr>
                        <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>
                        <td>{{ $user->country_code }}</td>
                        <td>{{ $user->last_seen_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td class="empty">Nobody is searching.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <section class="panel">
            <h2>Calls</h2>
            <table>
                <tbody>
                @forelse ($calls as $match)
                    <tr>
                        <td>#{{ $match->id }}</td>
                        <td>{{ $match->userOne->name ?? 'User' }} / {{ $match->userTwo->name ?? 'Waiting' }}</td>
                        <td><span class="badge ok">{{ $match->status }}</span></td>
                    </tr>
                @empty
                    <tr><td class="empty">No active calls.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
