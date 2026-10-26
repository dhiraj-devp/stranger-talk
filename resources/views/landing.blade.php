@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="cine">
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">Meet the world, one conversation at a time</p>
                <h1>The world is full of people you haven't met yet.</h1>
                <p>Meet someone new through a spontaneous one-to-one video conversation. No endless feeds. Just real-time conversations with people around the world.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
                    <a class="btn btn-ghost" href="#how">See how it works</a>
                </div>
                <p class="link-row"><a href="/random-video-chat">Random video chat</a><a href="/talk-to-strangers">Talk to strangers</a><a href="/omegle-alternative">Omegle alternative</a><a href="/features">Features</a><a href="/help">Help</a><a href="/about">About</a><a href="{{ route('guides') }}">Guides</a><a href="{{ route('blog') }}">Blog</a><a href="/contact">Contact</a></p>
                <ul class="trust">
                    <li>One-to-one video conversations</li>
                    <li>Simple browser-based experience</li>
                    <li>Built-in safety controls</li>
                </ul>
            </div>
            <div class="hero-stage">
                @include('partials.call-preview', ['size' => 'compact'])
            </div>
        </section>

        <section class="proof" aria-label="What {{ $brand->site_name }} offers">
            <p><strong>One-to-one video</strong><span>Focus on a single conversation.</span></p>
            <p><strong>Meet people worldwide</strong><span>Discover new perspectives.</span></p>
            <p><strong>Simple to start</strong><span>Sign in, set up your profile, and connect.</span></p>
        </section>

        <section id="how" class="section">
            <h2>Three steps. One new conversation.</h2>
            <p>Getting started should be effortless.</p>
            <ol class="steps">
                <li><span>01</span><div><h3>Sign in</h3><p>Create your account with Google to get started.</p></div></li>
                <li><span>02</span><div><h3>Set up your profile</h3><p>Complete the required profile details so you're ready to connect.</p></div></li>
                <li><span>03</span><div><h3>Start meeting people</h3><p>Enter the matching experience and begin a live video conversation.</p></div></li>
            </ol>
        </section>

        <section class="showcase">
            <div class="showcase-copy">
                <h2>Less scrolling. More talking.</h2>
                <p>The best conversations don't always come from a feed. Sometimes, they begin with someone you've never met.</p>
                <p>{{ $brand->site_name }} brings you into one live conversation at a time, with the freedom to continue or move on.</p>
            </div>
            @include('partials.call-preview')
        </section>

        <section class="section">
            <h2>Everything you need to connect.</h2>
            <p>A focused experience built around spontaneous conversations.</p>
            <div class="bento">
                <article class="glow">
                    <h3>Random matching</h3>
                    <p>Get paired with one available person for a one-to-one conversation.</p>
                </article>
                <article class="tall">
                    <h3>Live video</h3>
                    <p>Connect face-to-face directly through your browser.</p>
                </article>
                <article>
                    <h3>Simple call controls</h3>
                    <p>Manage your microphone and camera during a conversation.</p>
                </article>
                <article>
                    <h3>Move on anytime</h3>
                    <p>End the current interaction and request another match.</p>
                </article>
                <article>
                    <h3>Safety tools</h3>
                    <p>Block or report users when a conversation makes you uncomfortable.</p>
                </article>
                <article class="glow">
                    <h3>Privacy-conscious design</h3>
                    <p>Calls travel between browsers when a direct connection works. {{ $brand->site_name }} does not store the video or audio. A relay is used only if one is configured and the network needs it.</p>
                </article>
            </div>
        </section>

        <section class="section globe-section">
            <div>
                <h2>A bigger world. A new conversation.</h2>
                <p>Every person has a different story. Discover new perspectives, cultures, and experiences through spontaneous conversations with people you might never otherwise meet.</p>
            </div>
            <svg class="globe" viewBox="0 0 320 200" aria-hidden="true">
                <ellipse cx="160" cy="100" rx="78" ry="78" />
                <ellipse cx="160" cy="100" rx="78" ry="28" />
                <ellipse cx="160" cy="100" rx="28" ry="78" />
                <path d="M82 100h156M160 22v156" />
                <path d="M118 78 L196 92 L150 124 L118 78" />
                <circle cx="118" cy="78" r="3" />
                <circle cx="196" cy="92" r="3" />
                <circle cx="150" cy="124" r="3" />
            </svg>
        </section>

        <section id="safety" class="section">
            <h2>Your conversations. Your boundaries.</h2>
            <p>Meet new people while staying in control of your experience.</p>
            <div class="cards">
                <article><h3><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v6c0 4.2 2.8 7.4 7 9 4.2-1.6 7-4.8 7-9V6l-7-3Z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>Leave whenever you want</h3><p>End a call if you feel uncomfortable.</p></article>
                <article><h3><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>Block unwanted users</h3><p>Prevent further pairing with someone you block.</p></article>
                <article><h3><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h9l3 3v13H6V4Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.6"/></svg>Report inappropriate behavior</h3><p>Send a report for a moderator to review. Moderators see the report and account history, not a recording.</p></article>
                <article><h3><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>Understand your privacy</h3><p>Learn how video, account information, and technical logs are handled.</p></article>
            </div>
            <p class="link-row"><a href="/safety">Safety Center</a><a href="{{ route('guidelines') }}">Community Guidelines</a><a href="{{ route('privacy') }}">Privacy Policy</a></p>
        </section>

        <section id="premium" class="section premium">
            <div>
                <p class="pill">Coming soon</p>
                <h2>Make future conversations more personal.</h2>
                <p>@if (config('features.preference_matching')) Gender and country preferences are used when pairing. Anyone and any country still match freely. @else We're exploring additional matching preferences to give you more control over who you meet. Gender and country preferences can be saved now. They do not change matching yet. @endif</p>
            </div>
            <ul>
                <li>Gender preferences</li>
                <li>Country preferences</li>
            </ul>
        </section>

        <section id="faq" class="section">
            <h2>Questions, answered.</h2>
            <div class="faq">
                @foreach (\App\Services\SeoBuilder::faqs($brand->site_name) as $faq)
                    <details>
                        <summary>{{ $faq['q'] }}</summary>
                        <div class="faq-a"><p>{{ $faq['a'] }}</p></div>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="final">
            <h2>Your next great conversation could start here.</h2>
            <p>Step beyond your usual circle. Meet someone new, one conversation at a time.</p>
            <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
            <p class="fine">Sign in to get started. Please review our <a href="{{ route('guidelines') }}">safety guidelines</a> before connecting.</p>
        </section>
    </main>
    @include('partials.public-footer')
@endsection
