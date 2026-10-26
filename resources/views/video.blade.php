<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>Video Chat</title>
    <link rel="icon" href="{{ ($brand->faviconUrl()) }}">
    <link rel="stylesheet" href="{{ asset('css/room.css') }}?v={{ filemtime(public_path('css/room.css')) }}">
</head>
<body class="video-body">
    <header class="room-bar">
        @include('partials.brand', ['href' => route('home')])
        <div class="room-status">
            <p id="status" role="status">Allow camera and microphone</p>
            <p id="quality" hidden></p>
        </div>
        <details class="prefs">
            <summary>
                <span class="prefs-kicker">Preferences</span>
                <span id="pref-summary">{{ $preference->summary() }}</span>
            </summary>
            <form id="pref-form" class="prefs-card">
                <p class="prefs-title">Who you want to meet</p>
                <p class="prefs-lead">This choice is saved to your account and is how new matches are chosen.</p>
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
                <p class="fine">@if (config('features.preference_matching')) Used when pairing. Anyone and any country stay open. @else Saved for later. Matching does not use this until the feature is turned on. @endif</p>
                <p id="pref-state" class="fine" role="status"></p>
                <button type="submit">Save preferences</button>
            </form>
        </details>
        <details class="more">
            <summary aria-label="More options" title="More">More</summary>
            <div class="more-panel">
                <label for="cam-device">Camera</label>
                <select id="cam-device"></select>
                <label for="mic-device">Microphone</label>
                <select id="mic-device"></select>
                <label id="spk-label" for="spk-device" hidden>Speaker</label>
                <select id="spk-device" hidden></select>
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

    <div class="workspace">
        @php
            $me = auth()->user();
            $meCountry = \App\Support\Countries::valid($me->country_code) ? \App\Support\Countries::label($me->country_code) : '';
        @endphp
        <div class="stage">
            <section class="pane" id="remote-pane">
                <video id="remote" autoplay playsinline></video>
                <div class="pane-loading" id="remote-loading">
                    <span class="spinner" aria-hidden="true"></span>
                    <p>Finding someone</p>
                </div>
                <p class="identity">
                    <strong id="remote-name">Finding a match</strong>
                    <span id="remote-country"></span>
                </p>
            </section>
            <div id="local-wrap" class="pane local-wrap">
                <video id="local" autoplay playsinline muted></video>
                <div class="pane-loading" id="local-loading">
                    <span class="spinner" aria-hidden="true"></span>
                    <p>Finding someone</p>
                </div>
                <p class="identity">
                    <strong id="local-name">{{ $me->name }}</strong>
                    <span id="local-country">{{ $meCountry }}</span>
                </p>
                <p id="local-state" class="local-state" aria-live="polite"></p>
            </div>
            <p id="audio-tip" class="audio-tip" hidden>Tap to hear the other person</p>
        </div>
        <aside id="chat-panel" class="chat" hidden>
            <div class="chat-head">
                <h2>Chat</h2>
                <button id="chat-report" type="button">Report</button>
                <button id="chat-close" type="button" aria-label="Close chat">Close</button>
            </div>
            <div id="chat-log" class="chat-log" role="log" aria-live="polite" aria-relevant="additions"></div>
            <p id="chat-empty" class="chat-empty">Messages appear here once you are matched. They stay with this match only.</p>
            <p id="chat-error" class="chat-error" hidden></p>
            <form id="chat-form" class="chat-form">
                <label class="visually-hidden" for="chat-input">Message</label>
                <textarea id="chat-input" maxlength="500" rows="2" placeholder="Waiting for a match" disabled></textarea>
                <button id="chat-send" type="submit" disabled>Send</button>
            </form>
            <p class="chat-note">Saved with this match for {{ (int) config('platform.retention.match_events_days', 90) }} days.</p>
        </aside>
    </div>

    <div class="dock">
        <div class="controls" role="toolbar" aria-label="Call controls">
            <button id="mute" type="button" title="Mute microphone" aria-label="Mute microphone" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Zm-7 9a7 7 0 0 0 6 6.9V21H9v2h6v-2h-2v-2.1A7 7 0 0 0 19 12h-2a5 5 0 0 1-10 0H5Z"/></svg>
                <span>Mute</span>
            </button>
            <button id="chat-toggle" type="button" title="Chat" aria-label="Chat" aria-expanded="false" aria-controls="chat-panel">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-5 4v-4H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/></svg>
                <span>Chat</span>
                <b id="chat-unread" hidden>0</b>
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

    </div>

    <script type="application/json" id="ice-servers">@json($iceServers)</script>
    <script type="application/json" id="reverb-config">@json($reverb)</script>
    <script src="{{ asset('js/room-state.js') }}?v={{ filemtime(public_path('js/room-state.js')) }}"></script>
    <script src="{{ route('webrtc.script') }}"></script>
    <script src="{{ asset('js/room-ui.js') }}?v={{ filemtime(public_path('js/room-ui.js')) }}"></script>
    <script src="{{ asset('js/chat.js') }}?v={{ filemtime(public_path('js/chat.js')) }}"></script>
    <script src="{{ asset('js/pulse.js') }}?v={{ filemtime(public_path('js/pulse.js')) }}"></script>
</body>
</html>
