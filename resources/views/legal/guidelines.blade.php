@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <article class="card wide legal">
        <h1>Community Guidelines</h1>
        <p>Koko Meet is intended for adults aged 18 and above. The application does not currently verify age with a date of birth or an identity document. Do not use the service if you are under 18.</p>
        <ul>
            <li>Be decent. Leave if a conversation is uncomfortable.</li>
            <li>No harassment, threats, hate, spam, scams, or sexual content involving anyone under 18.</li>
            <li>No exploitation, coercion, doxxing, or attempts to identify, track, or pressure another person.</li>
            <li>Do not record, screenshot, redistribute, or threaten to distribute another participant’s image or video without consent.</li>
            <li>Use Report for safety issues and Block if you do not want to meet that person again. A moderator can review the report and account history. They cannot watch a recording of the call.</li>
            <li>The contact email is not an emergency service. If you are in immediate danger, contact local emergency services.</li>
            <li>Allow the camera and microphone only if you want to be seen and heard. You can mute your microphone during a call.</li>
        </ul>
        </article>
    </main>
    @include('partials.public-footer')
@endsection
