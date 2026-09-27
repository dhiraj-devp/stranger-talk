@if (! empty($seo->breadcrumbs) && count($seo->breadcrumbs) > 1)
    <nav class="crumbs" aria-label="Breadcrumb">
        @foreach ($seo->breadcrumbs as $crumb)
            @if ($crumb[1])
                <a href="{{ $crumb[1] }}">{{ $crumb[0] }}</a>
            @else
                <span aria-current="page">{{ $crumb[0] }}</span>
            @endif
        @endforeach
    </nav>
@endif
