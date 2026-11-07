@extends('layouts.marketing')

@section('content')
    @include('partials.public-header')
    <main class="cine">
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">India · One-to-one · Browser video chat</p>
                <h1>Random Video Chat in India – Meet New People Online</h1>
                <p>Looking to meet someone new in India? Koko Meet brings people together through spontaneous one-to-one video conversations. Sign in, connect with one available person, and start a face-to-face conversation. Talk, discover new perspectives, and move on whenever you want.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="{{ route('login') }}">Start Random Video Chat</a>
                    <a class="btn btn-ghost" href="#how">How It Works</a>
                </div>
                <ul class="trust">
                    <li>One-to-one conversations</li>
                    <li>Browser-based video chat</li>
                    <li>Block and report controls</li>
                </ul>
                <p class="fine">Matches are random. Koko Meet does not currently guarantee a match by gender, city, state, country, or language. Saved gender and country preferences do not currently affect matching.</p>
            </div>
            <div class="hero-stage">
                @include('partials.call-preview', ['size' => 'compact'])
            </div>
        </section>

        <section class="proof" aria-label="What {{ $brand->site_name }} offers">
            <p><strong>One-to-one video</strong><span>One available person at a time.</span></p>
            <p><strong>For adults in India</strong><span>Random matching. No city or language guarantee.</span></p>
            <p><strong>Sign in to start</strong><span>Google sign-in, then the required profile.</span></p>
        </section>

        <section class="section">
            <h2>Meet new people through random video chat</h2>
            <p>Sometimes the most interesting conversations begin with someone you have never met. Koko Meet gives you a simple way to meet another available person through a live one-to-one video conversation. Start with a hello, talk about your interests, and discover a new perspective.</p>
            <p>There is no public group room. If nobody is waiting, you stay in the queue until another person is available.</p>
        </section>

        <section class="section">
            <h2>Talk to strangers online, one person at a time</h2>
            <p>Koko Meet focuses on individual conversations rather than public group rooms. When another person is available, the matching system pairs you for a one-to-one conversation. You can talk through live video and use text chat during the call.</p>
            <p>Continue if the conversation feels right, use Next to look for another match, or End the call whenever you want.</p>
        </section>

        <section class="section">
            <h2>Meet people across India</h2>
            <p>India is home to many languages, cultures, interests, and experiences. A new conversation can introduce you to a different perspective or an experience outside your everyday circle. Koko Meet is positioned for adults in India who want to discover new conversations online.</p>
            <p>Matching remains random. A match is not limited to one state, city, or language, and the product does not guarantee that the other person is in India.</p>
        </section>

        <section class="section">
            <h2>Looking to make new friends online?</h2>
            <p>Meeting someone new can begin with a simple conversation. Introduce yourself, ask about a favourite movie, discuss music, talk about travel, or share an interest. Koko Meet provides a way to start a live conversation with one available person.</p>
            <p>It does not guarantee friendship, and it does not include a friend list or a way to request the same person again.</p>
        </section>

        <section id="how" class="section">
            <h2>How Koko Meet works</h2>
            <ol class="steps">
                <li><span>01</span><div><h3>Sign in</h3><p>Continue with Google to access Koko Meet.</p></div></li>
                <li><span>02</span><div><h3>Set up your profile</h3><p>Complete the required profile details.</p></div></li>
                <li><span>03</span><div><h3>Start matching</h3><p>Enter the matching experience and wait for another available person.</p></div></li>
                <li><span>04</span><div><h3>Start talking</h3><p>Connect through live video and text chat. Use the available controls to manage the conversation.</p></div></li>
            </ol>
        </section>

        <section class="section">
            <h2>Features for one-to-one conversations</h2>
            <div class="bento">
                <article><h3>Random matching</h3><p>Get paired with one available person at a time.</p></article>
                <article><h3>Live video</h3><p>Talk face-to-face through a supported browser.</p></article>
                <article><h3>Text chat</h3><p>Send messages during an active match. There is no separate text-only chat.</p></article>
                <article><h3>Call controls</h3><p>Mute the microphone. Choose a camera or microphone from the menu when the browser lists them.</p></article>
                <article><h3>Move on anytime</h3><p>Next leaves the current match and searches for another person.</p></article>
                <article><h3>End anytime</h3><p>End the call without starting another search.</p></article>
                <article><h3>Safety tools</h3><p>Block or report users when necessary.</p></article>
                <article class="glow"><h3>No stored recording</h3><p>Koko Meet does not store video or audio. A configured relay carries media only while the call lasts.</p></article>
            </div>
        </section>

        <section id="safety" class="section">
            <h2>Your conversations, your boundaries</h2>
            <p>You do not have to continue a conversation that makes you uncomfortable. Leave the call, use Block to prevent future pairing where supported, or submit a Report for moderation review.</p>
            <div class="cards">
                <article><h3>Leave</h3><p>Use End to leave, or Next to look for another available person.</p></article>
                <article><h3>Block</h3><p>Block is there so a later queue does not pair you with that account again.</p></article>
                <article><h3>Report</h3><p>Moderators can review the report text and permitted account history. They do not get a recording of the call.</p></article>
                <article><h3>Keep details private</h3><p>Never share your home address, phone number, financial information, passwords, school or workplace details, or private accounts.</p></article>
            </div>
            <p class="link-row"><a href="/safety">Safety Center</a><a href="{{ route('guidelines') }}">Community Guidelines</a><a href="{{ route('privacy') }}">Privacy Policy</a></p>
        </section>

        <section class="section">
            <h2>Privacy matters</h2>
            <p>Koko Meet does not store video or audio recordings. Media may travel directly between browsers when the network allows it. If a TURN relay is configured and a direct connection cannot be established, the relay may carry media for the duration of the call. That is transport, not a saved recording.</p>
            <p>This is not an anonymous product. You sign in with Google and complete a profile. Account information, match records, reports, chat retention, event logs, analytics, and IP handling are explained in the <a href="{{ route('privacy') }}">privacy policy</a>.</p>
        </section>

        <section id="faq" class="section">
            <h2>Frequently asked questions</h2>
            <div class="faq">
                @foreach (\App\Services\SeoBuilder::faqs($brand->site_name) as $faq)
                    <details>
                        <summary>{{ $faq['q'] }}</summary>
                        <div class="faq-a"><p>{{ $faq['a'] }}</p></div>
                    </details>
                @endforeach
            </div>
        </section>

        <nav class="section link-row" aria-label="More about Koko Meet">
            <a href="/random-video-chat">Random video chat</a>
            <a href="/random-video-chat-india">Random video chat in India</a>
            <a href="/one-to-one-video-chat">One-to-one video chat</a>
            <a href="/video-chat-with-strangers">Video chat with strangers</a>
            <a href="/talk-to-strangers">Talk to strangers</a>
            <a href="/meet-new-people-online">Meet new people</a>
            <a href="/make-friends-online">Make friends online</a>
            <a href="/omegle-alternative">Omegle alternative</a>
            <a href="/ometv-alternative">OmeTV alternative</a>
            <a href="{{ route('tools.starters') }}">Conversation starters</a>
            <a href="/safety">Safety</a>
            <a href="{{ route('blog') }}">Blog</a>
        </nav>

        <section class="final">
            <h2>Your Next Conversation Could Start Here</h2>
            <p>Meet someone new through a one-to-one random video conversation. Sign in and see who is available.</p>
            <a class="btn btn-primary" href="{{ route('login') }}">Start Random Video Chat</a>
        </section>
    </main>
    @include('partials.public-footer')
@endsection
