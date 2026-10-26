<header class="nav">
    <a class="mark" href="{{ route('landing') }}" tabindex="-1" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22"><rect x="3" y="6" width="18" height="12" rx="3" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="2.2" fill="currentColor"/></svg>
    </a>
    @include('partials.brand', ['href' => route('landing')])
    <nav class="nav-links" aria-label="Page">
        <a href="{{ route('landing') }}#how">How it works</a>
        <a href="{{ route('landing') }}#safety">Safety</a>
        <a href="{{ route('landing') }}#faq">FAQ</a>
        <a href="{{ route('blog') }}">Blog</a>
    </nav>
    <div class="nav-actions">
        <a class="nav-signin" href="{{ route('login') }}">Sign in</a>
        <a class="btn btn-primary" href="{{ $brand->header_cta_url ?: route('login') }}">{{ $brand->header_cta_label ?: 'Start Video Chat' }}</a>
    </div>
    <details class="nav-menu">
        <summary aria-label="Open menu">Menu</summary>
        <div>
            <a href="{{ route('landing') }}#how">How it works</a>
            <a href="{{ route('landing') }}#safety">Safety</a>
            <a href="{{ route('landing') }}#faq">FAQ</a>
            <a href="{{ route('blog') }}">Blog</a>
            <a href="{{ route('login') }}">Sign in</a>
        </div>
    </details>
</header>
