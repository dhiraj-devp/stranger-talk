@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')

    <main>
        <section class="hero">
            <div class="hero-copy">
                <h1>Meet someone new.<br>Anywhere in the world.</h1>
                <p>{{ $brand->site_name }} is a random video chat: one live, one-to-one conversation with a stranger.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
                    <a class="btn btn-ghost" href="#how">How it works</a>
                </div>
            </div>
            <div class="hero-stage">
                @include('partials.call-preview', ['size' => 'compact'])
            </div>
        </section>

        <section class="proof" aria-label="What {{ $brand->site_name }} is">
            <p>1-to-1 video conversations</p>
            <p>Connect globally</p>
            <p>No complicated setup</p>
        </section>

        <section id="how" class="section">
            <h2>How it works</h2>
            <ol class="steps">
                <li>
                    <span>01</span>
                    <div>
                        <h3>Sign in with Google</h3>
                        <p>One account. No extra password.</p>
                    </div>
                </li>
                <li>
                    <span>02</span>
                    <div>
                        <h3>Set up your profile</h3>
                        <p>Choose a gender and confirm your country.</p>
                    </div>
                </li>
                <li>
                    <span>03</span>
                    <div>
                        <h3>Meet someone new</h3>
                        <p>Get paired with one person for a live call.</p>
                    </div>
                </li>
            </ol>
        </section>

        <section class="showcase">
            <div class="showcase-copy">
                <h2>Just talk.</h2>
                <p>No feeds. No endless scrolling. Just one person and one conversation.</p>
            </div>
            @include('partials.call-preview')
        </section>

        <section class="section">
            <h2>Built for a single conversation</h2>
            <ul class="features">
                <li><h3>1-to-1 Video</h3><p>One stranger on screen.</p></li>
                <li><h3>Instant Matching</h3><p>Find someone who is free right now.</p></li>
                <li><h3>Camera &amp; Microphone Controls</h3><p>Mute or turn the camera off anytime.</p></li>
                <li><h3>Next Anytime</h3><p>Skip and look for someone else.</p></li>
                <li><h3>Block &amp; Report</h3><p>Stop a match and flag a problem.</p></li>
                <li><h3>Privacy Focused</h3><p>Calls are not stored by the platform.</p></li>
            </ul>
        </section>

        <section class="section globe-section">
            <div>
                <h2>People everywhere</h2>
                <p>When a direct connection works, video stays between the two browsers. A relay is used only if one is configured and the network needs it.</p>
            </div>
            <svg class="globe" viewBox="0 0 320 200" aria-hidden="true">
                <ellipse cx="160" cy="100" rx="78" ry="78" />
                <ellipse cx="160" cy="100" rx="78" ry="28" />
                <ellipse cx="160" cy="100" rx="28" ry="78" />
                <path d="M82 100h156M160 22v156" />
                <circle cx="118" cy="78" r="3" />
                <circle cx="196" cy="92" r="3" />
                <circle cx="150" cy="124" r="3" />
            </svg>
        </section>

        <section id="safety" class="section safety">
            <h2>Safety</h2>
            <ul>
                <li><strong>Block</strong> someone so you are not paired again.</li>
                <li><strong>Report</strong> a call for a moderator to review.</li>
                <li><a href="{{ route('guidelines') }}">Community guidelines</a> explain what is not allowed. <a href="/safety">Staying safe</a> covers the practical steps.</li>
                <li><strong>Private 1-to-1</strong> means one other person, not a room full of people.</li>
                <li>The platform does not record video or audio.</li>
            </ul>
        </section>

        <section class="section premium">
            <div>
                <p class="pill">Coming with Premium</p>
                <h2>Choose who you meet.</h2>
                <p>Gender and country preferences can be saved now. They do not change matching yet.</p>
            </div>
            <ul>
                <li>Gender preference</li>
                <li>Country preference</li>
                <li>More matching options</li>
            </ul>
        </section>

        <section id="faq" class="section">
            <h2>FAQ</h2>
            <div class="faq">
                @foreach (\App\Services\SeoBuilder::faqs($brand->site_name) as $faq)
                    <details>
                        <summary>{{ $faq['q'] }}</summary>
                        <p>{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="final">
            <h2>Ready to meet someone new?</h2>
            <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
        </section>
    </main>

    @include('partials.public-footer')
@endsection
