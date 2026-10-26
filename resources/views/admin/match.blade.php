@extends('layouts.admin')

@section('content')
    <h1>Match #{{ $match->id }}</h1>
    <p class="lead">{{ $match->status }} · {{ $match->end_reason ?: 'open' }} · {{ $seconds }} seconds. Media is not stored.</p>
    <section class="panel">
        <p>{{ $match->userOne->name ?? 'User' }} @if ($match->userOne)<span class="lead">{{ $match->userOne->email }}</span>@endif</p>
        <p>{{ $match->userTwo->name ?? 'Waiting' }} @if ($match->userTwo)<span class="lead">{{ $match->userTwo->email }}</span>@endif</p>
        <p class="lead">Started {{ $match->created_at }} @if ($match->ended_at) · Ended {{ $match->ended_at }} @endif</p>
    </section>
    <section class="panel" style="margin-top:16px">
        <h2>Timeline</h2>
        @forelse ($events as $event)
            <p>{{ $event->type }} · {{ $event->created_at }}</p>
        @empty
            <p class="empty">No events.</p>
        @endforelse
    </section>
@endsection
