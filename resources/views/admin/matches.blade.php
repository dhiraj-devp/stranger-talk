@extends('layouts.admin')

@section('content')
    <h1>Matches</h1>
    <p class="lead">Call records only. Media is not stored.</p>
    <section class="panel">
        <table>
            <thead><tr><th>ID</th><th>People</th><th>Status</th><th>Ended</th><th>Started</th></tr></thead>
            <tbody>
            @foreach ($matches as $match)
                <tr>
                    <td>#{{ $match->id }}</td>
                    <td>{{ $match->userOne->name ?? 'User' }} @if ($match->userTwo) / {{ $match->userTwo->name }} @endif</td>
                    <td><span class="badge">{{ $match->status }}</span></td>
                    <td>{{ $match->end_reason ?: '—' }}</td>
                    <td>{{ $match->created_at?->diffForHumans() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="pager">{{ $matches->links() }}</div>
    </section>
@endsection
