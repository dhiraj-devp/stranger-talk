@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="home">
        <section class="home-hero">
            <div class="home-copy">
                <h1>Random one-to-one video chat.</h1>
                <p>{{ $brand->site_name }} pairs you with one other person who is also looking. The call stays between the two of you. You can move to the next person or end it.</p>
                <p>For people who want one live conversation at a time. Sign in with Google, then allow the camera when the call starts.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
                    <a class="btn btn-ghost" href="#how">How it works</a>
                </div>
                <p class="home-links"><a href="/random-video-chat">Random video chat</a><a href="/talk-to-strangers">Talk to strangers</a><a href="/omegle-alternative">Omegle alternative</a><a href="/features">Features</a><a href="/safety">Safety</a><a href="{{ route('privacy') }}">Privacy</a></p>
            </div>
            <div class="home-preview">
                @include('partials.call-preview')
            </div>
        </section>

        <section id="how" class="home-block">
            <div class="home-head">
                <h2>How a call starts</h2>
                <p>Sign in, confirm two details, then wait for one person.</p>
            </div>
            <ol class="home-steps">
                <li>
                    <h3>Sign in with Google</h3>
                    <p>There is no separate password form. Google is the sign-in.</p>
                </li>
                <li>
                    <h3>Confirm your profile</h3>
                    <p>Gender and country are required before the first call. We do not guess them.</p>
                </li>
                <li>
                    <h3>Wait for one match</h3>
                    <p>You are paired with one available person, not a room of people.</p>
                </li>
            </ol>
        </section>

        <div class="home-split">
        <section class="home-block">
            <h2>What is on the call screen</h2>
            <ul class="home-list">
                <li>Two equal video panels, yours and the other person's.</li>
                <li>Name and country on each panel once a match starts.</li>
                <li>Mute, text chat, Next, and End.</li>
                <li>Report and block if you want that person stopped.</li>
            </ul>
        </section>

        <section id="safety" class="home-block">
            <h2>Safety</h2>
            <ul class="home-list">
                <li>End leaves the call.</li>
                <li>Block stops you from being paired with that person again.</li>
                <li>Report goes to a moderator with the reason you write. There is no recording to watch.</li>
                <li>{{ $brand->site_name }} does not store the video or audio. A relay is used only when one is configured and the network needs it.</li>
            </ul>
            <p class="home-links"><a href="/safety">Safety</a><a href="{{ route('guidelines') }}">Guidelines</a><a href="{{ route('privacy') }}">Privacy</a></p>
        </section>
        </div>

        <section class="home-block">
            <h2>Matching preferences</h2>
            <p>@if (config('features.preference_matching')) Gender and country preferences are used when pairing. Both people must accept each other. Anyone and any country stay unrestricted. @else You can save a gender and country preference. Matching does not use them until that option is turned on. @endif Blocks and bans still apply.</p>
        </section>

        <section id="faq" class="home-block">
            <h2>Questions</h2>
            <div class="faq">
                @foreach (\App\Services\SeoBuilder::faqs($brand->site_name) as $faq)
                    <details>
                        <summary>{{ $faq['q'] }}</summary>
                        <div class="faq-a"><p>{{ $faq['a'] }}</p></div>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="home-end">
            <h2>Start when you are signed in.</h2>
            <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
        </section>
    </main>
    @include('partials.public-footer')
@endsection
