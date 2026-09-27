@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="doc">
        @include('partials.breadcrumbs')
        <article class="card wide legal">
        <h1>Privacy</h1>
        <p>Random Video Chat pairs you with one other signed-in person for a live conversation.</p>
        <p>Video and audio are not stored by this application. Media is sent with WebRTC, directly between browsers when the network allows it. If a direct connection is not possible and a TURN server is configured, that server may relay the media only for the length of the call. This site does not record those streams.</p>
        <p>We store your account details (name, email, and a password hash or Google account id), match records, safety reports, blocks, bans, and limited event logs such as “call started” or “call ended.” We do not store the contents of a call.</p>
        <p>You can block someone so you are not paired again, and you can report a call. Moderators may review the report text and account history. They cannot watch a recording, because none is kept.</p>
        <p>Analytics counts events such as registrations and completed connections. Old analytics and match-event rows are deleted on the schedule configured for the server. Reports, bans, and audit logs are kept for moderation.</p>
        <p>This page describes how the software behaves. It is not legal advice.</p>
        </article>
    </main>
    @include('partials.public-footer')
@endsection
