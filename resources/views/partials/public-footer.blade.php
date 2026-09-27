<footer class="footer">
    <div>
        @include('partials.brand', ['href' => route('landing'), 'logoUrl' => $brand->footerLogoUrl()])
        @if ($brand->footer_text)<p>{{ $brand->footer_text }}</p>@endif
        <p>{{ $brand->copyright }}</p>
        @if ($brand->contact_email)<p><a href="mailto:{{ $brand->contact_email }}">{{ $brand->contact_email }}</a></p>@endif
    </div>
    <nav aria-label="Footer">
        @forelse ($footerNav as $item)
            <a href="{{ $item->url }}">{{ $item->label }}</a>
        @empty
            <a href="{{ route('privacy') }}">Privacy</a>
            <a href="{{ route('terms') }}">Terms</a>
            <a href="{{ route('guidelines') }}">Community Guidelines</a>
        @endforelse
        @foreach ($brand->socialLinks() as $social)
            <a href="{{ $social['url'] }}" rel="me noopener noreferrer">{{ $social['label'] }}</a>
        @endforeach
    </nav>
</footer>
