@php
    $role = auth()->user()->staffRole();
    $crumb = trim($__env->yieldContent('crumb')) ?: 'Overview';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? $crumb }} — {{ $brand->site_name }}</title>
    <link rel="icon" href="{{ $brand->faviconUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
    <div class="console">
        <aside class="side" id="side">
            @include('partials.brand', ['href' => $role === 'analyst' ? route('admin.analytics') : route('admin.dashboard')])
            @if ($role !== 'analyst')
                <p class="side-label">Command center</p>
                <nav aria-label="Command center">
                    <a href="{{ route('admin.dashboard') }}" @class(['is-on' => request()->routeIs('admin.dashboard')])>Overview</a>
                    <a href="{{ route('admin.live') }}" @class(['is-on' => request()->routeIs('admin.live')])>Live operations</a>
                </nav>
                <p class="side-label">User management</p>
                <nav aria-label="Users">
                    <a href="{{ route('admin.users') }}" @class(['is-on' => request()->routeIs('admin.users*') && request('role') !== 'admin'])>All users</a>
                    <a href="{{ route('admin.users', ['role' => 'admin']) }}" @class(['is-on' => request()->routeIs('admin.users') && request('role') === 'admin'])>Admin accounts</a>
                    <a href="{{ route('admin.bans') }}" @class(['is-on' => request()->routeIs('admin.bans')])>Bans</a>
                </nav>
                <p class="side-label">Trust and safety</p>
                <nav aria-label="Trust and safety">
                    <a href="{{ route('admin.reports') }}" @class(['is-on' => request()->routeIs('admin.reports*')])>Report queue @if ($pendingReports)<b>{{ $pendingReports }}</b>@endif</a>
                    @if ($role === 'admin')
                        <a href="{{ route('admin.audit', ['action' => 'resolve_report']) }}" @class(['is-on' => request('action') === 'resolve_report'])>Moderation history</a>
                    @endif
                </nav>
                <p class="side-label">Match operations</p>
                <nav aria-label="Matches">
                    <a href="{{ route('admin.matches') }}" @class(['is-on' => request()->routeIs('admin.matches*')])>Match history</a>
                </nav>
            @endif
            <p class="side-label">Analytics</p>
            <nav aria-label="Analytics">
                @foreach (['overview' => 'Overview', 'traffic' => 'Traffic', 'acquisition' => 'Acquisition', 'funnel' => 'Funnel', 'calls' => 'Calls', 'geography' => 'Geography', 'devices' => 'Devices', 'content' => 'Content', 'moderation' => 'Moderation'] as $key => $label)
                    <a href="{{ route('admin.analytics', ['section' => $key]) }}" @class(['is-on' => request()->routeIs('admin.analytics') && request()->route('section', 'overview') === $key])>{{ $label }}</a>
                @endforeach
            </nav>
            @if ($role === 'admin')
                <p class="side-label">System</p>
                <nav aria-label="System">
                    <a href="{{ route('admin.health') }}" @class(['is-on' => request()->routeIs('admin.health')])>System health</a>
                    <a href="{{ route('admin.settings') }}#limits" @class(['is-on' => request()->routeIs('admin.settings')])>Rate limits</a>
                </nav>
                <p class="side-label">Website and SEO</p>
                <nav aria-label="Website">
                    <a href="{{ route('admin.seo') }}" @class(['is-on' => request()->routeIs('admin.seo')])>SEO audit</a>
                    <a href="{{ route('admin.seo.edit') }}" @class(['is-on' => request()->routeIs('admin.seo.edit')])>Branding</a>
                    <a href="{{ route('admin.content', ['type' => 'page']) }}">Pages</a>
                    <a href="{{ route('admin.content', ['type' => 'post']) }}">Posts</a>
                    <a href="{{ route('admin.content', ['type' => 'guide']) }}">Guides</a>
                    <a href="{{ route('admin.media') }}" @class(['is-on' => request()->routeIs('admin.media')])>Media library</a>
                    <a href="{{ route('admin.redirects') }}" @class(['is-on' => request()->routeIs('admin.redirects')])>Redirects</a>
                    <a href="{{ route('admin.navigation') }}" @class(['is-on' => request()->routeIs('admin.navigation')])>Navigation</a>
                </nav>
                <p class="side-label">Governance</p>
                <nav aria-label="Governance">
                    <a href="{{ route('admin.settings') }}" @class(['is-on' => request()->routeIs('admin.settings')])>Matching and limits</a>
                    <a href="{{ route('admin.audit') }}" @class(['is-on' => request()->routeIs('admin.audit') && request('action') !== 'resolve_report'])>Audit trail</a>
                </nav>
            @endif
        </aside>
        <div class="main">
            <header class="top">
                <button class="menu-toggle" id="menu-toggle" type="button" aria-expanded="false" aria-controls="side">Menu</button>
                <p class="crumbs"><a href="{{ $role === 'analyst' ? route('admin.analytics') : route('admin.dashboard') }}">Control center</a> / {{ $crumb }}</p>
                @if ($role !== 'analyst')
                    <form class="search" method="GET" action="{{ route('admin.users') }}" role="search">
                        <input name="q" type="search" value="{{ request()->routeIs('admin.users') ? request('q') : '' }}" placeholder="Search name, email, or ID" aria-label="Search users">
                    </form>
                @endif
                <details class="account">
                    <summary>{{ auth()->user()->name }}</summary>
                    <div>
                        <p class="hint">{{ $role }}</p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Sign out</button>
                        </form>
                    </div>
                </details>
            </header>
            <div class="content">
                @if (session('status'))<p class="flash" role="status">{{ session('status') }}</p>@endif
                @if ($errors->any())
                    <div class="errors" role="alert">
                        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
</body>
</html>
