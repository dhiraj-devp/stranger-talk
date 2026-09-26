@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>System health</h1>
        @foreach ($health as $name => $value)
            <p>{{ $name }}: {{ is_array($value) ? json_encode($value) : $value }}</p>
        @endforeach
        <p class="switch"><a href="{{ route('admin.dashboard') }}">Dashboard</a></p>
    </section>
@endsection
