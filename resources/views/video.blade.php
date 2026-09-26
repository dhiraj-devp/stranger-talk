<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Random Video Chat</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="video-body">
    <header class="video-header">
        <h1>Random Video Chat</h1>
    </header>

    <div class="stage">
        <video id="remote" autoplay playsinline></video>
        <p class="tag remote-tag">Stranger Video</p>
        <div class="local-wrap">
            <video id="local" autoplay playsinline muted></video>
            <p class="tag">Your Video</p>
        </div>
        <p id="audio-tip" class="audio-tip" hidden>Click to enable audio</p>
    </div>

    <div class="dock">
        <div class="controls">
            <button id="mute" type="button" disabled>Mute</button>
            <button id="camera" type="button" disabled>Camera</button>
            <button id="next" type="button">Next</button>
            <button id="end" type="button" class="danger">End</button>
            <button id="again" type="button" hidden>Find Another</button>
        </div>
        <p id="status">Status: Searching for a stranger...</p>
    </div>

    <script type="application/json" id="ice-servers">@json($iceServers)</script>
    <script src="{{ route('webrtc.script') }}"></script>
</body>
</html>
