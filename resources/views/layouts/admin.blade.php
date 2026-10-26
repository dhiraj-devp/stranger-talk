<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Admin' }} — {{ $brand->site_name }}</title>
    <link rel="icon" href="{{ $brand->faviconUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
    <div class="console">
        <aside class="side">
            @include('partials.brand', ['href' => auth()->user()->staffRole() === 'analyst' ? route('admin.analytics') : route('admin.dashboard')])
            @if (auth()->user()->staffRole() === 'admin')
            <p class="side-label">Site</p>
            <nav aria-label="Site">
                <a href="{{ route('admin.seo') }}" @class(['is-on' => request()->routeIs('admin.seo')])>SEO</a>
                <a href="{{ route('admin.seo.edit') }}" @class(['is-on' => request()->routeIs('admin.seo.edit')])>Branding</a>
                <a href="{{ route('admin.content') }}" @class(['is-on' => request()->routeIs('admin.content')])>Content</a>
                <a href="{{ route('admin.content.create') }}" @class(['is-on' => request()->routeIs('admin.content.create', 'admin.content.edit')])>Editor</a>
                <a href="{{ route('admin.media') }}" @class(['is-on' => request()->routeIs('admin.media')])>Media</a>
                <a href="{{ route('admin.redirects') }}" @class(['is-on' => request()->routeIs('admin.redirects')])>Redirects</a>
                <a href="{{ route('admin.navigation') }}" @class(['is-on' => request()->routeIs('admin.navigation')])>Navigation</a>
            </nav>
            @endif
            <p class="side-label">Operations</p>
            <nav aria-label="Admin">
                @if (auth()->user()->staffRole() !== 'analyst')
                    <a href="{{ route('admin.dashboard') }}" @class(['is-on' => request()->routeIs('admin.dashboard')])>Overview</a>
                    <a href="{{ route('admin.live') }}" @class(['is-on' => request()->routeIs('admin.live')])>Live</a>
                    <a href="{{ route('admin.users') }}" @class(['is-on' => request()->routeIs('admin.users*')])>Users</a>
                    <a href="{{ route('admin.reports') }}" @class(['is-on' => request()->routeIs('admin.reports*')])>Reports @if ($pendingReports)<b>{{ $pendingReports }}</b>@endif</a>
                    <a href="{{ route('admin.bans') }}" @class(['is-on' => request()->routeIs('admin.bans')])>Bans</a>
                    <a href="{{ route('admin.matches') }}" @class(['is-on' => request()->routeIs('admin.matches*')])>Matches</a>
                @endif
                <a href="{{ route('admin.analytics') }}" @class(['is-on' => request()->routeIs('admin.analytics')])>Analytics</a>
                @if (auth()->user()->staffRole() === 'admin')
                    <a href="{{ route('admin.settings') }}" @class(['is-on' => request()->routeIs('admin.settings')])>Settings</a>
                    <a href="{{ route('admin.audit') }}" @class(['is-on' => request()->routeIs('admin.audit')])>Audit</a>
                    <a href="{{ route('admin.health') }}" @class(['is-on' => request()->routeIs('admin.health')])>System</a>
                @endif
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
