<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Random Video Chat' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="app-body">
    <header class="topnav">
        <a class="brand" href="{{ auth()->check() ? route('home') : route('landing') }}">StrangerTalk</a>
        @auth
            <nav class="topnav-links" aria-label="Main">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('video') }}">Video Chat</a>
                <a href="{{ route('profile.edit') }}">Profile</a>
            </nav>
            <details class="menu">
                <summary aria-label="Account menu">Account</summary>
                <div class="menu-panel">
                    <a href="{{ route('profile.edit') }}">Profile</a>
                    <a href="{{ route('profile.edit') }}#preferences">Preferences</a>
                    <a href="{{ route('guidelines') }}">Community Guidelines</a>
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                </div>
            </details>
        @endauth
    </header>
    <main class="page">
        @yield('content')
    </main>
    <footer class="site-footer">
        <a href="{{ route('privacy') }}">Privacy</a>
        <a href="{{ route('terms') }}">Terms</a>
        <a href="{{ route('guidelines') }}">Guidelines</a>
    </footer>
</body>
</html>
