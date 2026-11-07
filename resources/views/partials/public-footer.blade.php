<footer class="footer">
    <div class="foot-brand">
        @include('partials.brand', ['href' => route('landing'), 'logoUrl' => $brand->footerLogoUrl()])
        <p>Koko Meet is a browser-based random one-to-one video chat platform for adults looking to meet new people in India. Sign in, connect with one available person, and start a conversation.</p>
        @if ($brand->contact_email)<p><a href="mailto:{{ $brand->contact_email }}">{{ $brand->contact_email }}</a></p>@endif
    </div>
    <div class="foot-cols">
        <nav aria-label="Product">
            <p>Product</p>
            <a href="/random-video-chat">Random Video Chat</a>
            <a href="/one-to-one-video-chat">One-to-One Video Chat</a>
            <a href="/talk-to-strangers">Talk to Strangers</a>
            <a href="/meet-new-people-online">Meet New People</a>
            <a href="/features">Features</a>
        </nav>
        <nav aria-label="Explore">
            <p>Explore</p>
            <a href="{{ route('tools.starters') }}">Conversation Starters</a>
            <a href="{{ route('tools.questions') }}">Random Question Generator</a>
            <a href="{{ route('blog') }}">Blog</a>
            <a href="{{ route('guides') }}">Guides</a>
        </nav>
        <nav aria-label="Safety">
            <p>Safety</p>
            <a href="/safety">Safety Center</a>
            <a href="{{ route('guidelines') }}">Community Guidelines</a>
            <a href="{{ route('privacy') }}">Privacy Policy</a>
        </nav>
        <nav aria-label="Company">
            <p>Company</p>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
            <a href="{{ route('terms') }}">Terms of Service</a>
        </nav>
    </div>
    <p class="foot-copy">© {{ now()->year }} {{ $brand->site_name }}. Koko Meet is intended for adults aged 18 and above. Use the service respectfully and never share sensitive personal information with strangers.</p>
</footer>
