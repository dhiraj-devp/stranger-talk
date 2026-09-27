<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Admin' }} — StrangerTalk</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
    <div class="console">
        <aside class="side">
            <a class="brand" href="{{ route('admin.dashboard') }}">StrangerTalk</a>
            <p class="side-label">Operations</p>
            <nav aria-label="Admin">
                <a href="{{ route('admin.dashboard') }}" @class(['is-on' => request()->routeIs('admin.dashboard')])>Overview</a>
                <a href="{{ route('admin.live') }}" @class(['is-on' => request()->routeIs('admin.live')])>Live</a>
                <a href="{{ route('admin.users') }}" @class(['is-on' => request()->routeIs('admin.users*')])>Users</a>
                <a href="{{ route('admin.reports') }}" @class(['is-on' => request()->routeIs('admin.reports*')])>Reports @if ($pendingReports)<b>{{ $pendingReports }}</b>@endif</a>
                <a href="{{ route('admin.bans') }}" @class(['is-on' => request()->routeIs('admin.bans')])>Bans</a>
                <a href="{{ route('admin.matches') }}" @class(['is-on' => request()->routeIs('admin.matches')])>Matches</a>
                <a href="{{ route('admin.audit') }}" @class(['is-on' => request()->routeIs('admin.audit')])>Audit</a>
                <a href="{{ route('admin.health') }}" @class(['is-on' => request()->routeIs('admin.health')])>System</a>
            </nav>
        </aside>
        <div class="main">
            <header class="top">
                <p>{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Sign out</button>
                </form>
            </header>
            <div class="content">
                @if (session('status'))<p class="flash">{{ session('status') }}</p>@endif
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
