<header class="nav">
    @include('partials.brand', ['href' => route('landing')])
    <nav class="nav-links" aria-label="Page">
        @forelse ($headerNav as $item)
            <a href="{{ $item->url }}">{{ $item->label }}</a>
        @empty
            <a href="{{ route('landing') }}#how">How it works</a>
            <a href="{{ route('landing') }}#safety">Safety</a>
            <a href="{{ route('landing') }}#faq">FAQ</a>
        @endforelse
    </nav>
    <div class="nav-actions">
        <a class="nav-signin" href="{{ route('login') }}">Sign in</a>
        <a class="btn btn-primary" href="{{ $brand->header_cta_url ?: route('login') }}">{{ $brand->header_cta_label ?: 'Start Video Chat' }}</a>
    </div>
    <details class="nav-menu">
        <summary aria-label="Open menu">Menu</summary>
        <div>
            @forelse ($headerNav as $item)
                <a href="{{ $item->url }}">{{ $item->label }}</a>
            @empty
                <a href="{{ route('landing') }}#how">How it works</a>
                <a href="{{ route('landing') }}#safety">Safety</a>
                <a href="{{ route('landing') }}#faq">FAQ</a>
            @endforelse
            <a href="{{ route('login') }}">Sign in</a>
        </div>
    </details>
</header>
