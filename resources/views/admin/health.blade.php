@extends('layouts.admin')

@section('crumb', 'System health')

@section('content')
    <h1>System health</h1>
    <p class="lead">Read-only checks. A missing optional dependency is not an outage when polling can still carry video chat. Secrets are not shown.</p>
    <section class="metrics">
        @foreach ($health as $name => $value)
            @php
                $state = match ($value) {
                    'ok', 'configured' => 'Healthy',
                    'disabled', 'not_configured' => 'Not configured',
                    'unavailable' => 'Unavailable',
                    default => 'Unknown',
                };
            @endphp
            <article>
                <span>{{ $name }}</span>
                <strong>{{ $state }}</strong>
                <small>{{ $value }}</small>
            </article>
        @endforeach
    </section>
    <section class="panel">
        <h2>What these mean</h2>
        <p>Database and the jobs table must be reachable for normal operation.</p>
        <p>Redis is optional. It stays disabled until the platform is configured to use it.</p>
        <p>Reverb is optional realtime signaling. Calls still connect through HTTP polling when it is not configured.</p>
        <p>TURN credentials are not displayed here. The video page only receives a relay address when one is configured.</p>
    </section>
@endsection
