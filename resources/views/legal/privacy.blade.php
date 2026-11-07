@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <article class="card wide legal">
        <h1>Privacy</h1>
        <p>Koko Meet is a random one-to-one video chat. It is not an anonymous service: you sign in with Google and complete a profile. Matching pairs you with one other signed-in person who is available. It does not guarantee a match by gender, city, state, country, or language.</p>
        <p>Video and audio are not stored by this application. Media is sent with WebRTC, directly between browsers when the network allows it. If a direct connection is not possible and a TURN server is configured, that server may relay the media only for the length of the call. This site does not record those streams.</p>
        <p>We store your account details (name, email, and a password hash or Google account id), match records, safety reports, blocks, bans, and limited event logs such as “call started” or “call ended.” We do not store the contents of a call. Text you send in the match chat is stored with that match and deleted after {{ (int) config('platform.retention.match_events_days', 90) }} days.</p>
        <p>You can block someone so you are not paired again, and you can report a call. Moderators may review the report text and account history. They do not receive the chat transcript, and they cannot watch a recording, because none is kept.</p>
        <p>Analytics counts events such as registrations and completed connections. It also stores the page path, how long that page stayed visible, a device class (mobile, tablet, or desktop), the referring site host, a country code when the network provides one, and the IP address of the visit. Signed-out visitors are included. A random cookie ties those page views to one visit. Those rows are deleted after {{ (int) config('platform.retention.analytics_days', 180) }} days. Reports, bans, and audit logs are kept for moderation.</p>
        <p>The service is intended for adults aged 18 and above. The application does not currently collect a date of birth or verify age with an identity document.</p>
        <p>This page describes how the software behaves. It is not legal advice. The operator should review it against the services actually configured, including any relay, analytics, or sign-in provider.</p>
        </article>
    </main>
    @include('partials.public-footer')
@endsection
