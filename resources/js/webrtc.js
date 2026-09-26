const csrf = document.querySelector('meta[name="csrf-token"]').content;
const iceServers = JSON.parse(document.getElementById('ice-servers').textContent || '[]');

const remoteVideo = document.getElementById('remote');
const localVideo = document.getElementById('local');
const muteBtn = document.getElementById('mute');
const cameraBtn = document.getElementById('camera');
const nextBtn = document.getElementById('next');
const endBtn = document.getElementById('end');
const againBtn = document.getElementById('again');
const reportBtn = document.getElementById('report');
const blockBtn = document.getElementById('block');
const reportBox = document.getElementById('report-box');
const statusEl = document.getElementById('status');
const audioTip = document.getElementById('audio-tip');

let pageToken = newToken();
let pc = null;
let localStream = null;
let pollTimer = null;
let disconnectTimer = null;
let cursor = 0;
let pendingCandidates = [];
let offerApplied = false;
let answerApplied = false;
let stopped = false;
let busy = false;
let endingUi = false;
let joined = false;
let misses = 0;
let epoch = 0;
let micOn = true;
let camOn = true;
let iceRestarted = false;
let searchingAgain = false;
let reverbSocket = null;
let reverbSocketId = null;
const reverbEl = document.getElementById('reverb-config');
const reverb = reverbEl && reverbEl.textContent ? JSON.parse(reverbEl.textContent) : null;

muteBtn.addEventListener('click', () => {
    if (!localStream) return;
    micOn = !micOn;
    localStream.getAudioTracks().forEach((track) => {
        track.enabled = micOn;
    });
    label(muteBtn, micOn ? 'Mute' : 'Unmute', !micOn);
});

cameraBtn.addEventListener('click', () => {
    if (!localStream) return;
    camOn = !camOn;
    localStream.getVideoTracks().forEach((track) => {
        track.enabled = camOn;
    });
    label(cameraBtn, camOn ? 'Camera' : 'Camera off', !camOn);
});

nextBtn.addEventListener('click', onNext);
endBtn.addEventListener('click', onEnd);
if (reportBtn && reportBox) {
    reportBtn.addEventListener('click', () => {
        reportBox.hidden = !reportBox.hidden;
    });
    reportBox.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            await api('/match/report', {
                method: 'POST',
                body: JSON.stringify({
                    token: pageToken,
                    reason: document.getElementById('report-reason').value,
                    description: document.getElementById('report-note').value,
                }),
            });
            reportBox.hidden = true;
            setStatus('Report sent');
        } catch (error) {
            setStatus(error.message || 'Report could not be sent');
        }
    });
}
if (blockBtn) {
    blockBtn.addEventListener('click', async () => {
        if (!window.confirm('Block this stranger and end the call?')) return;
        try {
            await api('/match/block', { method: 'POST', body: JSON.stringify({ token: pageToken }) });
        } catch (error) {}
        onStrangerGone('blocked');
    });
}
againBtn.addEventListener('click', () => {
    pageToken = newToken();
    busy = false;
    endingUi = false;
    start();
});

document.body.addEventListener('click', () => {
    if (!audioTip.hidden && remoteVideo.srcObject) {
        remoteVideo.play().then(() => {
            audioTip.hidden = true;
        }).catch(() => {});
    }
});

window.addEventListener('pagehide', () => {
    if (!ignoreUnload()) beacon(pageToken);
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !stopped) pollOnce();
});

start();

async function start() {
    if (busy) return;
    busy = true;
    epoch += 1;
    endingUi = false;
    stopped = false;
    joined = false;
    misses = 0;
    resetPeerState();
    againBtn.hidden = true;
    nextBtn.disabled = false;
    endBtn.disabled = false;
    muteBtn.disabled = true;
    cameraBtn.disabled = true;
    audioTip.hidden = true;
    searchingAgain = false;
    setStatus('Finding someone...');

    try {
        if (!supportsWebRtc()) {
            failCall('This browser does not support video chat. Use a current version of Chrome, Firefox, Safari, or Edge.', false);
            return;
        }

        await ensureMedia();
        muteBtn.disabled = false;
        cameraBtn.disabled = false;
        const data = await api('/match/find', {
            method: 'POST',
            body: JSON.stringify({ token: pageToken }),
        });
        joined = true;
        await applyState(data);
        connectReverb();
        schedulePoll();
    } catch (error) {
        failCall(friendlyError(error), joined);
    } finally {
        busy = false;
    }
}

async function onNext() {
    if (busy || endingUi) return;
    busy = true;
    epoch += 1;
    const ticket = epoch;
    stopped = true;
    clearTimeout(pollTimer);
    const previousToken = pageToken;
    pageToken = newToken();
    closePeer();
    stopMedia();
    resetPeerState();
    searchingAgain = true;
    setStatus('Finding someone...');
    againBtn.hidden = true;

    try {
        const data = await api('/match/next', {
            method: 'POST',
            body: JSON.stringify({ token: previousToken, next_token: pageToken }),
        });
        if (ticket !== epoch) return;
        joined = true;
        await ensureMedia();
        muteBtn.disabled = false;
        cameraBtn.disabled = false;
        stopped = false;
        endingUi = false;
        await applyState(data);
        connectReverb();
        schedulePoll();
    } catch (error) {
        beacon(previousToken);
        failCall(friendlyError(error), true);
    } finally {
        busy = false;
    }
}

async function onEnd() {
    if (busy) return;
    busy = true;
    epoch += 1;
    stopped = true;
    window.__ignoreUnload = true;
    clearTimeout(pollTimer);
    closePeer();
    stopMedia();

    try {
        await api('/match/end', {
            method: 'POST',
            body: JSON.stringify({ token: pageToken }),
        });
    } catch (error) {
        beacon(pageToken);
    }

    window.location.href = '/home';
}

function schedulePoll() {
    clearTimeout(pollTimer);
    if (stopped) return;
    pollTimer = setTimeout(pollOnce, 1000);
}

async function pollOnce() {
    const ticket = epoch;
    if (stopped) return;

    try {
        const data = await api('/match/poll?token=' + encodeURIComponent(pageToken) + '&cursor=' + cursor);
        if (ticket !== epoch || stopped) return;
        misses = 0;

        try {
            await applyState(data);
        } catch (error) {
            if (ticket !== epoch) return;
            console.error(error);
            failCall('The call could not be connected. Please try again.', true);
            return;
        }
    } catch (error) {
        if (ticket !== epoch || stopped) return;
        misses += 1;
        if (misses >= 5) {
            onStrangerGone('poll-miss');
            return;
        }
    }

    if (ticket === epoch && !stopped) schedulePoll();
}

async function applyState(data) {
    if (!data || data.status === 'replaced') {
        stopped = true;
        return;
    }

    if (data.status === 'idle') return;

    if (data.status === 'ended' || data.status === 'failed' || data.status === 'expired') {
        onStrangerGone(data.status === 'failed' ? 'failed' : 'server:' + (data.end_reason || data.status));
        return;
    }

    if (data.status === 'waiting') {
        setStatus('Finding someone...');
        return;
    }

    if (data.status === 'connected' || (pc && pc.connectionState === 'connected')) {
        setStatus('Connected');
    } else {
        setStatus('Connecting...');
    }

    await ensurePeer(data.role);

    if (data.role === 'answerer' && data.offer && !offerApplied && pc) {
        await pc.setRemoteDescription(normalizeDescription(data.offer));
        offerApplied = true;
        await flushPending();
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        await api('/match/signal', {
            method: 'POST',
            body: JSON.stringify({
                token: pageToken,
                type: 'answer',
                payload: normalizeDescription(pc.localDescription),
            }),
        });
    }

    if (data.role === 'offerer' && data.answer && !answerApplied && pc) {
        await pc.setRemoteDescription(normalizeDescription(data.answer));
        answerApplied = true;
        await flushPending();
    }

    if (Array.isArray(data.candidates)) {
        for (const candidate of data.candidates) {
            await addCandidate(candidate);
        }
    }

    if (typeof data.cursor === 'number') cursor = data.cursor;
}

async function ensurePeer(role) {
    if (pc || !localStream) return;

    pc = new RTCPeerConnection({ iceServers });
    localStream.getTracks().forEach((track) => pc.addTrack(track, localStream));

    pc.ontrack = (event) => {
        remoteVideo.srcObject = event.streams[0] || new MediaStream([event.track]);
        remoteVideo.play().catch(() => {
            audioTip.hidden = false;
        });
    };

    pc.onicecandidate = (event) => {
        if (!event.candidate) return;
        api('/match/signal', {
            method: 'POST',
            body: JSON.stringify({
                token: pageToken,
                type: 'candidate',
                payload: {
                    candidate: event.candidate.candidate,
                    sdpMid: event.candidate.sdpMid,
                    sdpMLineIndex: event.candidate.sdpMLineIndex,
                    usernameFragment: event.candidate.usernameFragment,
                },
            }),
        }).catch(() => {});
    };

    pc.onconnectionstatechange = () => {
        if (!pc) return;
        if (pc.connectionState === 'connected') {
            clearTimeout(disconnectTimer);
            setStatus('Connected');
            api('/match/signal', {
                method: 'POST',
                body: JSON.stringify({ token: pageToken, type: 'connected', payload: {} }),
            }).catch(() => {});
        } else if (pc.connectionState === 'failed') {
            api('/match/signal', {
                method: 'POST',
                body: JSON.stringify({ token: pageToken, type: 'failed', payload: {} }),
            }).catch(() => {});
            onStrangerGone('webrtc-failed');
        } else if (pc.connectionState === 'disconnected') {
            if (!iceRestarted && typeof pc.restartIce === 'function') {
                iceRestarted = true;
                setStatus('Connection problem');
                try { pc.restartIce(); } catch (error) {}
            }
            clearTimeout(disconnectTimer);
            disconnectTimer = setTimeout(() => {
                if (pc && (pc.connectionState === 'disconnected' || pc.connectionState === 'failed')) {
                    onStrangerGone('webrtc-disconnected');
                }
            }, 8000);
        }
    };

    if (role === 'offerer') {
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        await api('/match/signal', {
            method: 'POST',
            body: JSON.stringify({
                token: pageToken,
                type: 'offer',
                payload: normalizeDescription(pc.localDescription),
            }),
        });
    }
}

async function addCandidate(candidate) {
    if (!candidate || !candidate.candidate || !pc) return;
    if (!pc.remoteDescription) {
        pendingCandidates.push(candidate);
        return;
    }

    try {
        await pc.addIceCandidate(candidate);
    } catch (error) {}
}

async function flushPending() {
    const queued = pendingCandidates.splice(0, pendingCandidates.length);
    for (const candidate of queued) {
        try {
            await pc.addIceCandidate(candidate);
        } catch (error) {}
    }
}

function onStrangerGone(source) {
    if (source === 'webrtc-failed' || source === 'failed') {
        againBtn.textContent = 'Try Again';
        failCall('Connection failed', true);
    } else if (source === 'poll-miss') {
        againBtn.textContent = 'Try Again';
        failCall('Connection problem', true);
    } else {
        againBtn.textContent = 'Find Another';
        failCall('Stranger disconnected', true);
    }
}

function failCall(message, notify) {
    if (endingUi) return;
    endingUi = true;
    stopped = true;
    clearTimeout(pollTimer);
    clearTimeout(disconnectTimer);
    closePeer();
    stopMedia();
    statusEl.textContent = message;
    statusEl.classList.add('is-error');
    againBtn.hidden = false;
    nextBtn.disabled = true;
    muteBtn.disabled = true;
    cameraBtn.disabled = true;
    endBtn.disabled = false;
    if (notify && joined) beacon(pageToken);
}

function closePeer() {
    if (!pc) return;
    const connection = pc;
    pc = null;
    connection.onicecandidate = null;
    connection.ontrack = null;
    connection.onconnectionstatechange = null;
    connection.close();
}

function stopMedia() {
    if (localStream) {
        localStream.getTracks().forEach((track) => track.stop());
    }
    localStream = null;
    localVideo.srcObject = null;
    remoteVideo.srcObject = null;
}

async function ensureMedia() {
    if (localStream) return;
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
    } catch (error) {
        const videoOk = await probeMedia({ video: true });
        const audioOk = await probeMedia({ audio: true });
        if (!videoOk && audioOk) {
            const denied = new Error('Camera unavailable');
            denied.name = 'CameraUnavailable';
            throw denied;
        }
        if (videoOk && !audioOk) {
            const denied = new Error('Microphone unavailable');
            denied.name = 'MicUnavailable';
            throw denied;
        }
        throw error;
    }
    localVideo.srcObject = localStream;
    micOn = true;
    camOn = true;
    label(muteBtn, 'Mute', false);
    label(cameraBtn, 'Camera', false);
}

function resetPeerState() {
    offerApplied = false;
    answerApplied = false;
    pendingCandidates = [];
    cursor = 0;
    iceRestarted = false;
    clearTimeout(disconnectTimer);
}

function beacon(token) {
    const body = new FormData();
    body.append('_token', csrf);
    body.append('token', token);
    navigator.sendBeacon('/match/leave', body);
}

function ignoreUnload() {
    return window.__ignoreUnload === true;
}

function supportsWebRtc() {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.RTCPeerConnection);
}

function label(button, text, pressed) {
    const span = button.querySelector('span');
    if (span) span.textContent = text;
    else button.textContent = text;
    button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
    button.title = text;
    button.setAttribute('aria-label', text);
}

function setStatus(text) {
    statusEl.classList.remove('is-error');
    statusEl.textContent = text;
}

async function probeMedia(constraints) {
    try {
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        stream.getTracks().forEach((track) => track.stop());
        return true;
    } catch (error) {
        return false;
    }
}

function friendlyError(error) {
    const name = error && error.name;
    if (name === 'CameraUnavailable') return 'Camera unavailable';
    if (name === 'MicUnavailable') return 'Microphone unavailable';
    if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
        return 'Camera or microphone permission was denied. Allow access in the browser and try again.';
    }
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError' || name === 'OverconstrainedError') {
        return 'No camera or microphone is available on this device.';
    }
    if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'AbortError') {
        return 'Camera or microphone is unavailable. It may be in use by another app.';
    }
    if (error instanceof Error && error.message) return error.message;
    return 'Camera or microphone could not be started.';
}

async function api(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            ...(options.headers || {}),
        },
        ...options,
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const validation = data.errors && Object.values(data.errors)[0];
        const message = (Array.isArray(validation) && validation[0]) || data.message || 'Something went wrong. Please try again.';
        throw new Error(message);
    }
    return data;
}

function normalizeDescription(description) {
    const sdp = String(description.sdp || '')
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n')
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== '')
        .join('\r\n');

    return { type: description.type, sdp: sdp + '\r\n' };
}

function connectReverb() {
    if (!reverb || !reverb.key || !window.WebSocket) return;
    const scheme = reverb.scheme === 'https' ? 'wss' : 'ws';
    const url = scheme + '://' + reverb.host + ':' + reverb.port + '/app/' + encodeURIComponent(reverb.key) + '?protocol=7&client=js&version=8.4.0&flash=false';
    if (reverbSocket) {
        try { reverbSocket.close(); } catch (error) {}
    }
    reverbSocket = new WebSocket(url);
    reverbSocket.onmessage = async (event) => {
        let payload;
        try { payload = JSON.parse(event.data); } catch (error) { return; }
        if (payload.event === 'pusher:connection_established') {
            reverbSocketId = JSON.parse(payload.data).socket_id;
            await subscribeSignal();
        } else if (payload.event === 'pusher:ping' && reverbSocket.readyState === 1) {
            reverbSocket.send(JSON.stringify({ event: 'pusher:pong', data: {} }));
        } else if (payload.event === 'signal' && !stopped) {
            pollOnce();
        }
    };
}

async function subscribeSignal() {
    if (!reverbSocket || reverbSocket.readyState !== 1 || !reverbSocketId) return;
    const channel = 'private-signals.' + pageToken;
    const body = new URLSearchParams();
    body.set('socket_id', reverbSocketId);
    body.set('channel_name', channel);
    const response = await fetch('/broadcasting/auth', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrf,
        },
        body,
    });
    if (!response.ok) return;
    const auth = await response.json();
    reverbSocket.send(JSON.stringify({ event: 'pusher:subscribe', data: { channel, auth: auth.auth } }));
}

function newToken() {
    if (window.crypto && typeof crypto.randomUUID === 'function') return crypto.randomUUID();
    return 't-' + Math.random().toString(16).slice(2) + Date.now().toString(16);
}
