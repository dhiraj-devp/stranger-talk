@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Random Video Chat</h1>
        <p class="welcome">Welcome, {{ auth()->user()->name }}</p>

        <a class="button" href="{{ route('video') }}">Find Stranger</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="secondary">Logout</button>
        </form>
    </section>
@endsection
