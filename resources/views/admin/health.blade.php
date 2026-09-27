@extends('layouts.admin')

@section('content')
    <h1>System</h1>
    <section class="metrics">
        @foreach ($health as $name => $value)
            <article>
                <span>{{ $name }}</span>
                <strong>{{ is_array($value) ? json_encode($value) : $value }}</strong>
            </article>
        @endforeach
    </section>
    <p class="lead">Redis and Reverb show as disabled or not configured until those services are running. That does not stop video chat, which still uses polling.</p>
@endsection
