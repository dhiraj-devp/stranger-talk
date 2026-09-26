@extends('layouts.marketing')

@section('title', 'Sign in — StrangerTalk')
@section('robots', 'noindex,nofollow')
@section('canonical', route('login'))
@section('description', 'Sign in to start a conversation.')

@section('content')
    <main class="auth-shell">
        <a class="brand" href="{{ route('landing') }}">StrangerTalk</a>
        <h1>Meet someone new.</h1>
        <p>Sign in to start a conversation.</p>

        @if ($errors->any())
            <div class="alert" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <a class="btn btn-google" href="{{ route('auth.google') }}">Continue with Google</a>
        <p class="fine">By continuing, you agree to our <a href="{{ route('terms') }}">Terms</a> and <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>
    </main>
@endsection
