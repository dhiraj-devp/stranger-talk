@extends('layouts.marketing')

@section('title', 'Meet someone new — StrangerTalk')
@section('description', 'Random 1-to-1 video conversations with people around the world.')
@section('canonical', url('/'))

@section('content')
    <header class="nav">
        <a class="brand" href="{{ route('landing') }}">StrangerTalk</a>
        <nav class="nav-links" aria-label="Page">
            <a href="#how">How it works</a>
            <a href="#safety">Safety</a>
            <a href="#faq">FAQ</a>
        </nav>
        <div class="nav-actions">
            <a class="nav-signin" href="{{ route('login') }}">Sign in</a>
            <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
        </div>
        <details class="nav-menu">
            <summary aria-label="Open menu">Menu</summary>
            <div>
                <a href="#how">How it works</a>
                <a href="#safety">Safety</a>
                <a href="#faq">FAQ</a>
                <a href="{{ route('login') }}">Sign in</a>
            </div>
        </details>
    </header>

    <main>
        <section class="hero">
            <div class="hero-copy">
                <h1>Meet someone new.<br>Anywhere in the world.</h1>
                <p>Random 1-to-1 video conversations with people around the world.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
                    <a class="btn btn-ghost" href="#how">How it works</a>
                </div>
            </div>
            <div class="hero-stage">
                @include('partials.call-preview', ['size' => 'compact'])
            </div>
        </section>

        <section class="proof" aria-label="What StrangerTalk is">
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
                <li><strong>Community guidelines</strong> explain what is not allowed.</li>
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
                <details>
                    <summary>Do you record video calls?</summary>
                    <p>No. Video and audio are not stored by StrangerTalk.</p>
                </details>
                <details>
                    <summary>How does matching work?</summary>
                    <p>You are paired at random with one other person who is also looking.</p>
                </details>
                <details>
                    <summary>Can I skip someone?</summary>
                    <p>Yes. Next ends the current call and looks for someone else.</p>
                </details>
                <details>
                    <summary>What happens if someone behaves badly?</summary>
                    <p>Leave the call, then report or block them from the menu.</p>
                </details>
                <details>
                    <summary>Do I need an account?</summary>
                    <p>Yes. Sign in with Google, then confirm a short profile the first time.</p>
                </details>
            </div>
        </section>

        <section class="final">
            <h2>Ready to meet someone new?</h2>
            <a class="btn btn-primary" href="{{ route('login') }}">Start Video Chat</a>
        </section>
    </main>

    <footer class="footer">
        <a class="brand" href="{{ route('landing') }}">StrangerTalk</a>
        <nav aria-label="Footer">
            <a href="{{ route('privacy') }}">Privacy</a>
            <a href="{{ route('terms') }}">Terms</a>
            <a href="{{ route('guidelines') }}">Community Guidelines</a>
            <a href="#faq">FAQ</a>
        </nav>
    </footer>
@endsection
