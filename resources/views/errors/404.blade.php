@extends('layouts.app')
@section('content')
    <section class="card"><h1>Page not found</h1><p>That address does not exist.</p><p class="switch"><a href="{{ url('/') }}">Home</a></p></section>
@endsection
