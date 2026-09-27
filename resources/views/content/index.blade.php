@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <h1>{{ $name }}</h1>
        <p>{{ $description }}</p>
        @forelse ($items as $item)
            <article>
                <h2><a href="{{ $item->publicPath() }}">{{ $item->title }}</a></h2>
                @if ($item->excerpt)<p>{{ $item->excerpt }}</p>@endif
            </article>
        @empty
            <p>Nothing has been published here yet.</p>
        @endforelse
        <div class="pager">{{ $items->links() }}</div>
    </main>
    @include('partials.public-footer')
@endsection
