@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Reset password</h1>
        @if (session('status'))
            <p class="note">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            <button type="submit">Send reset link</button>
        </form>
        <p class="switch"><a href="{{ route('login') }}">Back to login</a></p>
    </section>
@endsection
