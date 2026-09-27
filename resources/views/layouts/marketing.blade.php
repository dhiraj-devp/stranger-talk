@php
    $yieldTitle = trim($__env->yieldContent('title'));
    $yieldDescription = trim($__env->yieldContent('description'));
    $yieldCanonical = trim($__env->yieldContent('canonical'));
    $yieldRobots = trim($__env->yieldContent('robots'));
    $title = isset($seo) ? $seo->title : ($yieldTitle !== '' ? $yieldTitle : $brand->default_title);
    $description = isset($seo) ? $seo->description : ($yieldDescription !== '' ? $yieldDescription : $brand->default_description);
    $canonical = isset($seo) ? $seo->canonical : ($yieldCanonical !== '' ? $yieldCanonical : $brand->absolute('/'));
    $robots = isset($seo) ? $seo->robots : ($yieldRobots !== '' ? $yieldRobots : 'index,follow');
    $ogTitle = isset($seo) ? $seo->ogTitle : $title;
    $ogDescription = isset($seo) ? $seo->ogDescription : $description;
    $ogImage = isset($seo) ? $seo->ogImage : $brand->assetAbsolute($brand->og_image);
    $twitterTitle = isset($seo) ? $seo->twitterTitle : $title;
    $twitterDescription = isset($seo) ? $seo->twitterDescription : $description;
    $twitterImage = isset($seo) ? $seo->twitterImage : ($brand->assetAbsolute($brand->twitter_image) ?: $ogImage);
@endphp
<!DOCTYPE html>
<html lang="{{ $brand->language ?: 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="{{ $robots }}">
    @if ($brand->gsc_verification)
        <meta name="google-site-verification" content="{{ $brand->gsc_verification }}">
    @endif
    <meta property="og:site_name" content="{{ $brand->site_name }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('-', '_', $brand->locale ?: 'en') }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $twitterImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    @if ($twitterImage)
        <meta name="twitter:image" content="{{ $twitterImage }}">
    @endif
    @isset($seo)
        @if ($seo->prev)<link rel="prev" href="{{ $seo->prev }}">@endif
        @if ($seo->next)<link rel="next" href="{{ $seo->next }}">@endif
    @endisset
    <meta name="theme-color" content="{{ $brand->theme_color ?: '#7c3aed' }}">
    <link rel="icon" href="{{ $brand->faviconUrl() }}" type="{{ str_ends_with($brand->faviconUrl(), '.svg') ? 'image/svg+xml' : 'image/png' }}">
    @if ($brand->assetAbsolute($brand->apple_touch_icon))
        <link rel="apple-touch-icon" href="{{ $brand->assetAbsolute($brand->apple_touch_icon) }}">
    @endif
    <link rel="manifest" href="{{ route('manifest') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @if (is_file(public_path('css/brand.css')))
        <link rel="stylesheet" href="{{ asset('css/brand.css') }}?v={{ filemtime(public_path('css/brand.css')) }}">
    @endif
    @isset($seo)
        <script type="application/ld+json">{!! $seo->jsonLd !!}</script>
    @endisset
    @if (app()->environment('production') && ($brand->ga_measurement_id || $brand->gtm_id))
        <script src="{{ asset('js/analytics.js') }}" @if ($brand->gtm_id) data-gtm="{{ $brand->gtm_id }}" @endif @if ($brand->ga_measurement_id) data-ga="{{ $brand->ga_measurement_id }}" @endif></script>
        @if ($brand->gtm_id)
            <script async src="https://www.googletagmanager.com/gtm.js?id={{ $brand->gtm_id }}"></script>
        @elseif ($brand->ga_measurement_id)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $brand->ga_measurement_id }}"></script>
        @endif
    @endif
</head>
<body class="@yield('body-class', 'marketing')">
    @yield('content')
</body>
</html>
