@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Account suspended</h1>
        <p>{{ auth()->user()->ban_reason ?: 'Your account cannot start new chats right now.' }}</p>
        @if (auth()->user()->banned_until)
            <p>Access returns {{ auth()->user()->banned_until->timezone(config('app.timezone'))->toDayDateTimeString() }}.</p>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="secondary">Logout</button>
        </form>
    </section>
@endsection
