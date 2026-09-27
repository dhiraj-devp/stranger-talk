@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <article class="card wide legal">
        <h1>Terms</h1>
        <p>You must be allowed to use this kind of service where you live. You are responsible for what you say and show on camera.</p>
        <p>Do not harass, threaten, or share illegal sexual content. We may suspend an account, temporarily or for good, when a report or other account signal supports that action.</p>
        <p>The service is provided as available. Calls can fail because of browsers, permissions, or networks. We do not promise that every pair will connect.</p>
        <p>Sign-in uses Google when the site operator has configured it.</p>
        </article>
    </main>
    @include('partials.public-footer')
@endsection
