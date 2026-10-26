@extends('layouts.admin')

@section('content')
    <h1>SEO</h1>
    <p class="lead">Public pages only. This does not measure Google rankings or traffic.</p>
    @if ($disallowAll)<p class="flash">Crawlers are blocked from the whole site.</p>@endif
    <section class="metrics">
        <article><span>Indexable URLs</span><strong>{{ $indexable }}</strong></article>
        <article><span>Noindex pages</span><strong>{{ $noindex }}</strong></article>
        <article><span>Missing descriptions</span><strong>{{ $missingDescription }}</strong></article>
        <article><span>Duplicate titles</span><strong>{{ $duplicateTitles->count() }}</strong></article>
        <article><span>Duplicate descriptions</span><strong>{{ $duplicateDescriptions->count() }}</strong></article>
        <article><span>Custom canonicals</span><strong>{{ $customCanonicals }}</strong></article>
        <article><span>Posts without an image</span><strong>{{ $noImage }}</strong></article>
        <article><span>Images missing alt</span><strong>{{ $missingAlt }}</strong></article>
    </section>
    <p class="lead">Sitemap: <a href="{{ route('sitemap') }}">/sitemap.xml</a> · Robots: <a href="{{ route('robots') }}">/robots.txt</a>. Empty canonical fields still get a generated canonical. There is no external crawl and no Search Console count until you add that property yourself. Set the canonical base to https://kokomeet.live, paste the Search Console verification token, submit https://kokomeet.live/sitemap.xml, then use URL Inspection. A finding here is not an indexing result.</p>
    <section class="panel">
        <h2>Technical issues</h2>
        @forelse ($findings as $finding)
            <p>{{ strtoupper($finding['severity']) }} {{ $finding['url'] }} — {{ $finding['description'] }} {{ $finding['fix'] }}</p>
        @empty
            <p class="empty">No findings from the local check.</p>
        @endforelse
    </section>
    @if ($queryReport)
        <section class="panel">
            <h2>Imported queries</h2>
            <pre>{{ $queryReport }}</pre>
        </section>
    @endif
    <div class="split">
        <section class="panel">
            <h2>Duplicate titles</h2>
            @forelse ($duplicateTitles as $title)<p>{{ $title }}</p>@empty<p class="empty">None</p>@endforelse
            <h2>Internal links to check</h2>
            @forelse ($broken as $item)<p>{{ $item->label }} → {{ $item->url }}</p>@empty<p class="empty">Navigation paths match a known public page.</p>@endforelse
        </section>
        <section class="panel">
            <h2>Recently updated</h2>
            @foreach ($recent as $item)
                <p><a href="{{ route('admin.content.edit', $item) }}">{{ $item->title }}</a> · {{ $item->status }}</p>
            @endforeach
        </section>
    </div>
@endsection
