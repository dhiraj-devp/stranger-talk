@extends('layouts.app')

@section('content')
    <section class="card wide">
        <h1>Users</h1>
        <form method="GET" action="{{ route('admin.users') }}">
            <label for="q">Search</label>
            <input id="q" name="q" value="{{ $q }}" placeholder="Name or email">
            <button type="submit">Search</button>
        </form>
        @foreach ($users as $user)
            <p><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a> — {{ $user->email }} — {{ $user->status }}</p>
        @endforeach
        {{ $users->links() }}
        <p class="switch"><a href="{{ route('admin.dashboard') }}">Dashboard</a></p>
    </section>
@endsection
