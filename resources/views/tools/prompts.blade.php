@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <article>
            <h1>{{ $heading }}</h1>
            <p class="lead">{{ $intro }}</p>
            <div class="tool-box">
                <label for="prompt-category">Category</label>
                <select id="prompt-category">
                    <option value="all">All</option>
                    @foreach (array_keys($groups) as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                <p id="prompt-text">{{ $payload['flat'][0] ?? '' }}</p>
                <div class="tool-actions">
                    <button class="btn btn-primary" id="prompt-next" type="button">{{ $button }}</button>
                    <button class="btn btn-ghost" id="prompt-copy" type="button">Copy</button>
                </div>
            </div>
            @foreach ($groups as $name => $items)
                <h2>{{ $name }}</h2>
                <ul class="prompt-list">
                    @foreach ($items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endforeach
            <p>Matches on Koko Meet are random. These ideas do not choose who you meet, and they do not guarantee a language, city, or gender.</p>
            <p><a class="btn btn-primary" href="{{ route('login') }}">Start Random Video Chat</a></p>
            <p class="link-row"><a href="{{ $cta }}">Related page</a><a href="/safety">Safety Center</a><a href="{{ route('tools.starters') }}">Conversation starters</a></p>
        </article>
        <script type="application/json" id="prompt-data">@json($payload)</script>
    </main>
    @include('partials.public-footer')
    <script src="{{ asset('js/prompts.js') }}?v={{ filemtime(public_path('js/prompts.js')) }}"></script>
@endsection
