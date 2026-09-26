@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Random Video Chat</h1>
        <p class="lead">Log in to meet a stranger.</p>

        @if ($errors->any())
            <div class="alert" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">

            <button type="submit">Login</button>
        </form>

        <p class="switch">No account? <a href="{{ route('register') }}">Register</a></p>
    </section>
@endsection
