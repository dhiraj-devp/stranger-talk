<header class="nav">
    <a class="mark" href="{{ route('landing') }}" tabindex="-1" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22"><rect x="3" y="6" width="18" height="12" rx="3" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="2.2" fill="currentColor"/></svg>
    </a>
    @include('partials.brand', ['href' => route('landing')])
    <nav class="nav-links" aria-label="Page">
        <a href="{{ route('landing') }}#how">How It Works</a>
        <a href="/random-video-chat">Random Video Chat</a>
        <a href="/safety">Safety</a>
        <a href="{{ route('blog') }}">Blog</a>
    </nav>
    <div class="nav-actions">
        <a class="nav-signin" href="{{ route('login') }}">Sign In</a>
        <a class="btn btn-primary" href="{{ $brand->header_cta_url ?: route('login') }}">{{ $brand->header_cta_label ?: 'Start Video Chat' }}</a>
    </div>
    <details class="nav-menu">
        <summary aria-label="Open menu">
            <svg class="nav-burger" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </summary>
        <div>
            <a href="{{ route('landing') }}#how">How It Works</a>
            <a href="/random-video-chat">Random Video Chat</a>
            <a href="/safety">Safety</a>
            <a href="{{ route('blog') }}">Blog</a>
            <p class="fine">Explore</p>
            <a href="/talk-to-strangers">Talk to Strangers</a>
            <a href="/meet-new-people-online">Meet New People</a>
            <a href="{{ route('tools.starters') }}">Conversation Starters</a>
            <a href="{{ route('login') }}">Sign In</a>
        </div>
    </details>
</header>
