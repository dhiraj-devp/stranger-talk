@extends('layouts.app')

@section('content')
    <section class="card wide">
        <h1>Blocked people</h1>
        <p class="lead">Blocked strangers will not be matched with you again.</p>
        @forelse ($blocks as $block)
            <form method="POST" action="{{ route('blocks.destroy', $block) }}" class="row">
                @csrf
                @method('DELETE')
                <span>Blocked stranger</span>
                <button type="submit" class="secondary">Unblock</button>
            </form>
        @empty
            <p>You have not blocked anyone.</p>
        @endforelse
        <p class="switch"><a href="{{ route('home') }}">Back</a></p>
    </section>
@endsection
