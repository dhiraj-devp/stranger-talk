<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>Video Chat</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/room.css') }}?v={{ filemtime(public_path('css/room.css')) }}">
</head>
<body class="video-body">
    <header class="room-bar">
        <a class="brand" href="{{ route('home') }}">StrangerTalk</a>
        <p id="status" role="status">Finding someone...</p>
        <details class="more">
            <summary aria-label="More options" title="More">More</summary>
            <div class="more-panel">
                <button id="report" type="button">Report</button>
                <button id="block" type="button">Block</button>
                <a href="{{ route('guidelines') }}">Community Guidelines</a>
                <a href="{{ route('profile.edit') }}">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </div>
        </details>
    </header>

    <div class="stage">
        <video id="remote" autoplay playsinline></video>
        <p class="tag remote-tag">Stranger</p>
        <div id="local-wrap" class="local-wrap">
            <video id="local" autoplay playsinline muted></video>
            <p class="tag">You</p>
            <p id="local-state" class="local-state" aria-live="polite"></p>
        </div>
        <p id="audio-tip" class="audio-tip" hidden>Tap to hear the other person</p>
    </div>

    <div class="dock">
        <div class="controls" role="toolbar" aria-label="Call controls">
            <button id="mute" type="button" title="Mute microphone" aria-label="Mute microphone" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Zm-7 9a7 7 0 0 0 6 6.9V21H9v2h6v-2h-2v-2.1A7 7 0 0 0 19 12h-2a5 5 0 0 1-10 0H5Z"/></svg>
                <span>Mute</span>
            </button>
            <button id="camera" type="button" title="Turn camera off" aria-label="Turn camera off" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h11a2 2 0 0 1 2 2v1.2l3-1.8v9.2l-3-1.8V16a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"/></svg>
                <span>Camera</span>
            </button>
            <button id="next" type="button" class="next" title="Next stranger" aria-label="Next stranger">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h2v12H5V6Zm4.5 0 9 6-9 6V6Z"/></svg>
                <span>Next</span>
            </button>
            <button id="end" type="button" class="danger" title="End call" aria-label="End call">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8h12v2H6V8Zm1 4h10l-1 7H8l-1-7Z"/></svg>
                <span>End</span>
            </button>
            <button id="again" type="button" hidden>Find Another</button>
        </div>

        <form id="report-box" hidden>
            <label for="report-reason">Reason</label>
            <select id="report-reason">
                <option value="harassment">Harassment</option>
                <option value="nudity">Nudity or sexual content</option>
                <option value="spam">Spam</option>
                <option value="abuse">Abuse</option>
                <option value="suspicious">Suspicious behavior</option>
                <option value="other">Other</option>
            </select>
            <label for="report-note">Details (optional)</label>
            <textarea id="report-note" maxlength="500"></textarea>
            <button id="report-send" type="submit">Send report</button>
        </form>

        <details class="prefs">
            <summary>Match Preferences <span id="pref-summary">{{ $preference->summary() }}</span></summary>
            <form id="pref-form">
                <label for="gender_preference">Gender</label>
                <select id="gender_preference" name="gender_preference">
                    @foreach (['anyone' => 'Anyone', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" @selected($preference->gender_preference === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label for="country_preference">Country</label>
                <select id="country_preference" name="country_preference">
                    <option value="any" @selected($preference->country_preference === null)>Any Country</option>
                    @foreach ($countries as $code => $name)
                        <option value="{{ $code }}" @selected($preference->country_preference === $code)>{{ \App\Support\Countries::flag($code) }} {{ $name }}</option>
                    @endforeach
                </select>
                <p class="fine">Premium filters coming soon</p>
                <button type="submit">Save Preferences</button>
            </form>
        </details>
    </div>

    <script type="application/json" id="ice-servers">@json($iceServers)</script>
    <script type="application/json" id="reverb-config">@json($reverb)</script>
    <script src="{{ route('webrtc.script') }}"></script>
    <script src="{{ asset('js/room-ui.js') }}?v={{ filemtime(public_path('js/room-ui.js')) }}"></script>
</body>
</html>
