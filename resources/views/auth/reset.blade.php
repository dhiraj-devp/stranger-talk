@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Choose a new password</h1>
        @if ($errors->any())
            <div class="alert" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required>
            <label for="password">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            <button type="submit">Update password</button>
        </form>
    </section>
@endsection
