@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <article class="card wide legal">
        <h1>Terms</h1>
        <p>Koko Meet is intended for adults aged 18 and above. The software does not currently verify age with a date of birth or an identity document. You must be allowed to use this kind of service where you live, and you are responsible for what you say and show on camera.</p>
        <p>Do not harass, threaten, or share illegal sexual content. We may suspend an account, temporarily or for good, when a report or other account signal supports that action.</p>
        <p>The service is provided as available. Calls can fail because of browsers, permissions, or networks. Matching is random and depends on someone else being available. We do not promise a match by gender, place, or language, and we do not promise that every pair will connect. Koko Meet does not charge a fee to sign in or start a call. There is no paid plan in the application.</p>
        <p>Sign-in uses Google when the site operator has configured it.</p>
        </article>
    </main>
    @include('partials.public-footer')
@endsection
