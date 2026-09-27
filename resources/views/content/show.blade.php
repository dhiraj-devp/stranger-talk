@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <article>
            <h1>{{ $content->title }}</h1>
            @if ($content->excerpt)<p class="lead">{{ $content->excerpt }}</p>@endif
            @if ($content->featured_image)
                <img src="{{ $brand->assetAbsolute($content->featured_image) }}" alt="{{ $content->image_alt }}" @if ($content->image_width) width="{{ $content->image_width }}" height="{{ $content->image_height }}" @endif decoding="async">
            @endif
            @if ($content->type === 'faq' && $content->faqPairs() !== [])
                @foreach ($content->faqPairs() as $faq)
                    <h2>{{ $faq['q'] }}</h2>
                    <p>{{ $faq['a'] }}</p>
                @endforeach
            @elseif ($content->type === 'glossary' && $content->glossaryTerms() !== [])
                <dl>
                    @foreach ($content->glossaryTerms() as $term)
                        <dt>{{ $term['term'] }}</dt>
                        <dd>{{ $term['definition'] }}</dd>
                    @endforeach
                </dl>
            @else
                @foreach ($content->blocks() as $block)
                    @if ($block['type'] === 'h2')
                        <h2>{{ $block['text'] }}</h2>
                    @else
                        <p>{{ $block['text'] }}</p>
                    @endif
                @endforeach
            @endif
            @if ($content->published_at)
                <p class="meta">Published {{ $content->published_at->toFormattedDateString() }}@if ($content->updated_at) · Updated {{ $content->updated_at->toFormattedDateString() }}@endif</p>
            @endif
        </article>
        @if ($related->isNotEmpty())
            <aside class="related">
                <h2>Related</h2>
                <ul>
                    @foreach ($related as $item)
                        <li><a href="{{ $item->publicPath() }}">{{ $item->title }}</a></li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </main>
    @include('partials.public-footer')
@endsection
