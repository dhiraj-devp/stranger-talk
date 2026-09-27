@php($logoUrl = $logoUrl ?? $brand->headerLogoUrl())
<a class="brand" href="{{ $href }}">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $brand->site_name }}" height="32">
    @else
        {{ $brand->site_name }}
    @endif
</a>
