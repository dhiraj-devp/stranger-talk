@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Create an account</h1>
        <p class="lead">Then find a stranger.</p>

        <a class="button google" href="{{ route('auth.google') }}">Continue with Google</a>
        <p class="or">or use email</p>

        @if ($errors->any())
            <div class="alert" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name">

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">

            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

            <button type="submit">Register</button>
        </form>

        <p class="switch">Already registered? <a href="{{ route('login') }}">Login</a></p>
    </section>
@endsection
