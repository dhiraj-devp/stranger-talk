<footer class="footer">
    <div class="foot-brand">
        @include('partials.brand', ['href' => route('landing'), 'logoUrl' => $brand->footerLogoUrl()])
        <p>One-to-one video chat. Sign in, get paired with one person, and leave when you want.</p>
        @if ($brand->contact_email)<p><a href="mailto:{{ $brand->contact_email }}">{{ $brand->contact_email }}</a></p>@endif
    </div>
    <div class="foot-cols">
        <nav aria-label="Product"><p>Product</p><a href="{{ route('landing') }}#how">How it works</a><a href="/features">Features</a><a href="/random-video-chat">Random video chat</a><a href="{{ route('login') }}">Start Video Chat</a></nav>
        <nav aria-label="Safety"><p>Safety</p><a href="/safety">Safety</a><a href="{{ route('guidelines') }}">Guidelines</a><a href="/help">Help</a><a href="{{ route('privacy') }}">Privacy</a></nav>
        <nav aria-label="Explore"><p>Explore</p><a href="/about">About</a><a href="/talk-to-strangers">Talk to strangers</a><a href="/omegle-alternative">Omegle alternative</a><a href="{{ route('blog') }}">Blog</a><a href="{{ route('guides') }}">Guides</a></nav>
        <nav aria-label="Legal"><p>Legal</p><a href="{{ route('privacy') }}">Privacy</a><a href="{{ route('terms') }}">Terms</a><a href="/contact">Contact</a></nav>
    </div>
    <p class="foot-copy">© {{ now()->year }} {{ $brand->site_name }}. All rights reserved.</p>
</footer>
