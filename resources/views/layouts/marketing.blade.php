<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Meet someone new')</title>
    <meta name="description" content="@yield('description', 'Instantly connect with a random person through a private 1-to-1 video conversation.')">
    <link rel="canonical" href="@yield('canonical', url('/'))">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta property="og:title" content="@yield('title', 'Meet someone new')">
    <meta property="og:description" content="@yield('description', 'Instantly connect with a random person through a private 1-to-1 video conversation.')">
    <meta property="og:url" content="@yield('canonical', url('/'))">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', 'Meet someone new')">
    <meta name="twitter:description" content="@yield('description', 'Instantly connect with a random person through a private 1-to-1 video conversation.')">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="@yield('body-class', 'marketing')">
    @yield('content')
</body>
</html>
