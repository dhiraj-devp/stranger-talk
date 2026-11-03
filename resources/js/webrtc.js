const csrf = document.querySelector('meta[name="csrf-token"]').content;
let iceServerList = null;

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
let lastOfferSdp = '';
let lastAnswerSdp = '';
let peerRole = '';
let remoteStream = null;
let cursorHold = false;
let stopped = false;
let busy = false;
let endingUi = false;
let joined = false;
let misses = 0;
let epoch = 0;
let micOn = true;
let camOn = true;
let iceRestarts = 0;
let restartingNow = false;
let publishing = false;
let failureTimer = null;
let mediaReadySent = false;
let diagSerial = 0;
const iceErrors = [];
const localCandidateLog = [];
const remoteCandidateLog = [];
let signalVersion = 0;
let holdLocalCandidates = false;
let heldLocal = [];
let pollInFlight = false;
let pollAgain = false;
let remoteSoundOn = false;
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

if (cameraBtn) {
    cameraBtn.addEventListener('click', () => {
        if (!localStream) return;
        camOn = !camOn;
        localStream.getVideoTracks().forEach((track) => {
            track.enabled = camOn;
        });
        label(cameraBtn, camOn ? 'Camera' : 'Camera off', !camOn);
    });
}

function cameraReady(ready) {
    if (cameraBtn) cameraBtn.disabled = !ready;
}

function paintPartner(data) {
    const nameEl = document.getElementById('remote-name');
    const countryEl = document.getElementById('remote-country');
    const loadingText = document.querySelector('#remote-loading p');
    if (!nameEl) return;
    const partner = data && data.partner;
    if (partner && partner.name) {
        nameEl.textContent = partner.name;
        if (countryEl) countryEl.textContent = partner.country || '';
        if (loadingText) loadingText.textContent = 'Connecting...';
    } else {
        nameEl.textContent = 'Finding a match';
        if (countryEl) countryEl.textContent = '';
        if (loadingText) loadingText.textContent = 'Finding someone';
        document.body.classList.remove('has-remote');
    }
}

nextBtn.addEventListener('click', function () {
    if (busy) return;
    const live = document.body.classList.contains('room-live') && !endingUi;
    if (live && nextBtn.querySelector('span') && nextBtn.querySelector('span').textContent !== 'Confirm') {
        label(nextBtn, 'Confirm', false);
        return;
    }
    label(nextBtn, 'Next', false);
    if (endingUi) {
        pageToken = newToken();
        busy = false;
        endingUi = false;
        start();
        return;
    }
    onNext();
});
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
if (againBtn) {
    againBtn.addEventListener('click', () => {
        pageToken = newToken();
        busy = false;
        endingUi = false;
        start();
    });
}

document.body.addEventListener('click', () => {
    if (!audioTip.hidden && remoteVideo.srcObject) {
        remoteVideo.muted = false;
        remoteVideo.removeAttribute('muted');
        remoteSoundOn = true;
        remoteVideo.play().then(() => {
            audioTip.hidden = true;
        }).catch(() => {});
    }
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !stopped) pollOnce();
});

start();

function announce(status) {
    document.dispatchEvent(new CustomEvent('koko:match', {
        detail: { token: pageToken, status: status },
    }));
}

async function start() {
    if (busy) return;
    busy = true;
    epoch += 1;
    announce('reset');
    endingUi = false;
    stopped = false;
    joined = false;
    misses = 0;
    resetPeerState();
    if (againBtn) againBtn.hidden = true;
    nextBtn.disabled = false;
    endBtn.disabled = false;
    muteBtn.disabled = true;
    cameraReady(false);
    audioTip.hidden = true;
    searchingAgain = false;
    if (window.RoomState) window.RoomState.set('permissions');
    setStatus('Allow camera and microphone');

    try {
        if (!supportsWebRtc()) {
            failCall('This browser does not support video chat. Use a current version of Chrome, Firefox, Safari, or Edge.', false);
            return;
        }

        await ensureMedia();
        muteBtn.disabled = false;
        cameraReady(true);
        setStatus('Finding someone...');
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
    announce('reset');
    document.body.classList.add('is-skipping');
    paintPartner(null);
    closePeer();
    stopMedia();
    resetPeerState();
    searchingAgain = true;
    setStatus('Finding someone...');
    if (againBtn) againBtn.hidden = true;

    try {
        const data = await api('/match/next', {
            method: 'POST',
            body: JSON.stringify({ token: previousToken, next_token: pageToken }),
        });
        if (ticket !== epoch) return;
        joined = true;
        await ensureMedia();
        muteBtn.disabled = false;
        cameraReady(true);
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

    announce('ended');
    window.location.href = '/home';
}

function schedulePoll() {
    clearTimeout(pollTimer);
    if (stopped) return;
    pollTimer = setTimeout(pollOnce, 1000);
}

async function pollOnce() {
    if (stopped) return;
    if (pollInFlight) {
        pollAgain = true;
        return;
    }
    pollInFlight = true;
    const ticket = epoch;
    try {
        const data = await api('/match/poll?token=' + encodeURIComponent(pageToken) + '&cursor=' + cursor);
        if (ticket !== epoch || stopped) return;
        misses = 0;

        try {
            await applyState(data);
        } catch (error) {
            if (ticket !== epoch) return;
            console.error(error);
        }
    } catch (error) {
        if (ticket !== epoch || stopped) return;
        misses += 1;
        if (misses >= 15) {
            onStrangerGone('poll-miss');
            return;
        }
    } finally {
        pollInFlight = false;
    }

    if (ticket !== epoch || stopped) return;
    if (pollAgain) {
        pollAgain = false;
        pollOnce();
        return;
    }
    schedulePoll();
}

async function applyState(data) {
    if (!data || data.status === 'replaced') {
        stopped = true;
        announce('replaced');
        return;
    }

    if (data.status === 'idle') {
        announce('idle');
        return;
    }

    if (data.status === 'ended' || data.status === 'failed' || data.status === 'expired') {
        onStrangerGone(data.status === 'failed' ? 'failed' : 'server:' + (data.end_reason || data.status));
        return;
    }

    if (data.status === 'waiting') {
        setStatus('Finding someone...');
        announce('waiting');
        paintPartner(null);
        return;
    }

    announce(data.status);
    paintPartner(data);
    if (typeof data.v === 'number' && data.v > 0) signalVersion = data.v;
    if (data.status === 'connected') document.body.classList.remove('is-skipping');

    const pictureIn = remoteVideo.videoWidth > 0;
    if (data.status === 'connected' || (pc && pc.connectionState === 'connected') || pictureIn) {
        setStatus('Connected');
    } else {
        setStatus('Connecting...');
    }

    await ensurePeer(data.role);

    let restarting = false;

    if (data.role === 'answerer' && data.offer && pc) {
        const nextSdp = data.offer.sdp || '';
        if (!offerApplied || lastOfferSdp !== nextSdp) {
            const replacing = offerApplied && lastOfferSdp !== nextSdp;
            if (replacing) pendingCandidates = [];
            try {
                const description = normalizeDescription(data.offer);
                if (pc.signalingState !== 'have-remote-offer') {
                    await pc.setRemoteDescription(description);
                }
                await flushPending();
                if (!pc.localDescription || pc.localDescription.type !== 'answer' || replacing) {
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                }
                const answerPayload = normalizeDescription(pc.localDescription);
                if (signalVersion > 0) answerPayload.v = signalVersion;
                await api('/match/signal', {
                    method: 'POST',
                    body: JSON.stringify({
                        token: pageToken,
                        type: 'answer',
                        payload: answerPayload,
                    }),
                });
                lastOfferSdp = nextSdp;
                offerApplied = true;
                if (replacing) restarting = true;
            } catch (error) {
                console.error(error);
            }
        }
    }

    if (data.role === 'offerer' && data.answer && pc) {
        const nextSdp = data.answer.sdp || '';
        if (!answerApplied || lastAnswerSdp !== nextSdp) {
            try {
                await pc.setRemoteDescription(normalizeDescription(data.answer));
                lastAnswerSdp = nextSdp;
                answerApplied = true;
                await flushPending();
            } catch (error) {
                console.error(error);
            }
        }
    }

    if (Array.isArray(data.candidates)) {
        for (const candidate of data.candidates) {
            await addCandidate(candidate);
        }
    }

    if (restarting || cursorHold) {
        cursor = 0;
        cursorHold = false;
    } else if (typeof data.cursor === 'number') {
        cursor = data.cursor;
    }
}

function postCandidate(candidate) {
    const payload = {
        candidate: candidate.candidate,
        sdpMid: candidate.sdpMid,
        sdpMLineIndex: candidate.sdpMLineIndex,
        usernameFragment: candidate.usernameFragment,
    };
    if (signalVersion > 0) payload.v = signalVersion;
    api('/match/signal', {
        method: 'POST',
        body: JSON.stringify({
            token: pageToken,
            type: 'candidate',
            payload: payload,
        }),
    }).catch(() => {});
}

function queueOrSendCandidate(candidate) {
    if (holdLocalCandidates || !signalVersion) {
        heldLocal.push(candidate);
        return;
    }
    postCandidate(candidate);
}

async function flushLocalCandidates() {
    const queued = heldLocal.splice(0, heldLocal.length);
    for (const candidate of queued) postCandidate(candidate);
}

async function publishOffer(extra) {
    if (publishing || !pc) return;
    publishing = true;
    const restarting = !!(extra && extra.iceRestart);
    if (restarting) heldLocal = [];
    holdLocalCandidates = true;
    try {
        if (restarting && (pc.signalingState === 'have-local-offer' || pc.signalingState === 'have-remote-offer')) {
            try { await pc.setLocalDescription({ type: 'rollback' }); } catch (error) {}
        }
        const offer = await pc.createOffer(extra || {});
        await pc.setLocalDescription(offer);
        const saved = await api('/match/signal', {
            method: 'POST',
            body: JSON.stringify({
                token: pageToken,
                type: 'offer',
                payload: normalizeDescription(pc.localDescription),
            }),
        });
        if (saved && saved.v) signalVersion = saved.v;
    } finally {
        holdLocalCandidates = false;
        publishing = false;
    }
    await flushLocalCandidates();
}

function scheduleRestart() {
    if (failureTimer || iceRestarts >= 1) return;
    failureTimer = setTimeout(() => {
        failureTimer = null;
        if (!pc) return;
        if (pc.connectionState === 'connected' || pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed') return;
        if (pc.iceConnectionState !== 'failed' && pc.connectionState !== 'failed') return;
        restartOffer().catch(() => {});
    }, 12000);
}

async function restartOffer() {
    if (!pc || peerRole !== 'offerer' || restartingNow || publishing || iceRestarts >= 1) return;
    restartingNow = true;
    cursorHold = true;
    cursor = 0;
    try {
        await publishOffer({ iceRestart: true });
        iceRestarts += 1;
    } catch (error) {
        holdLocalCandidates = false;
        console.error(error);
    } finally {
        restartingNow = false;
    }
}

function markConnected() {
    clearTimeout(failureTimer);
    failureTimer = null;
    iceRestarts = 0;
    setStatus('Connected');
    remoteVideo.play().catch(() => {});
    watchQuality();
    if (mediaReadySent) return;
    mediaReadySent = true;
    api('/match/signal', {
        method: 'POST',
        body: JSON.stringify({ token: pageToken, type: 'connected', payload: {} }),
    }).catch(() => {});
}

function showRemoteTrack(track) {
    if (!remoteStream) remoteStream = new MediaStream();
    if (track && !remoteStream.getTracks().some((item) => item.id === track.id)) {
        remoteStream.addTrack(track);
    }
    remoteVideo.srcObject = remoteStream;
    if (!remoteSoundOn) {
        remoteVideo.muted = true;
        remoteVideo.setAttribute('muted', '');
    }
    remoteVideo.playsInline = true;
    document.body.classList.add('has-remote');
    const play = () => {
        remoteVideo.play().catch(() => {});
    };
    play();
    if (track) track.onunmute = play;
    if (!remoteSoundOn) audioTip.hidden = false;
}

async function loadIceServers() {
    if (iceServerList) return iceServerList;
    const data = await api('/api/webrtc/ice-servers');
    const servers = data && data.iceServers;
    if (!Array.isArray(servers) || servers.length === 0) {
        throw new Error('Video relay did not return a server list.');
    }
    iceServerList = servers;
    return iceServerList;
}

async function ensurePeer(role) {
    if (role) peerRole = role;
    if (pc || !localStream) return;

    let servers;
    try {
        servers = await loadIceServers();
    } catch (error) {
        const message = error && error.message ? error.message : 'Video relay is unavailable. Try again in a moment.';
        failCall(message, joined);
        throw error;
    }

    diagSerial += 1;
    pc = new RTCPeerConnection({
        iceServers: servers,
        bundlePolicy: 'max-bundle',
        rtcpMuxPolicy: 'require',
    });
    pc.onicecandidate = (event) => {
        if (!event.candidate) return;
        rememberLocalCandidate(event.candidate);
        queueOrSendCandidate(event.candidate);
    };
    pc.onicecandidateerror = (event) => {
        rememberIceError(event);
    };
    localStream.getTracks().forEach((track) => pc.addTrack(track, localStream));

    pc.ontrack = (event) => {
        showRemoteTrack(event.track);
    };

    pc.oniceconnectionstatechange = () => {
        if (!pc) return;
        if (pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed') {
            clearTimeout(failureTimer);
            failureTimer = null;
            remoteVideo.play().catch(() => {});
            if (pc.connectionState === 'connected') markConnected();
            return;
        }
        if (pc.iceConnectionState === 'failed') scheduleRestart();
    };

    pc.onconnectionstatechange = () => {
        if (!pc) return;
        if (pc.connectionState === 'connected') {
            clearTimeout(disconnectTimer);
            markConnected();
        } else if (pc.connectionState === 'failed') {
            scheduleRestart();
        }
    };

    if (role === 'offerer') {
        await publishOffer();
    }
}

async function addCandidate(candidate) {
    if (!candidate || !candidate.candidate) return;
    rememberRemoteCandidate(candidate);
    if (!pc || !pc.remoteDescription) {
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
        failCall('Connection failed', true);
    } else if (source === 'poll-miss') {
        failCall('Connection problem', true);
    } else {
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
    document.body.classList.remove('is-skipping');
    announce('ended');
    paintPartner(null);
    paintQuality('', false);
    if (window.RoomState) {
        const lower = String(message).toLowerCase();
        if (lower.indexOf('permission') !== -1 || lower.indexOf('camera') !== -1 || lower.indexOf('microphone') !== -1 || lower.indexOf('browser') !== -1) {
            window.RoomState.set('permissions');
        } else if (message === 'Stranger disconnected') {
            window.RoomState.set('partner-left');
        } else {
            window.RoomState.set('ended');
        }
    }
    if (againBtn) againBtn.hidden = true;
    nextBtn.disabled = false;
    muteBtn.disabled = true;
    cameraReady(false);
    endBtn.disabled = false;
    if (notify && joined) beacon(pageToken);
}

function closePeer() {
    clearInterval(qualityTimer);
    clearTimeout(failureTimer);
    failureTimer = null;
    if (!pc) return;
    const connection = pc;
    pc = null;
    connection.onicecandidate = null;
    connection.onicecandidateerror = null;
    connection.ontrack = null;
    connection.onconnectionstatechange = null;
    connection.oniceconnectionstatechange = null;
    connection.close();
}

function stopMedia() {
    if (localStream) {
        localStream.getTracks().forEach((track) => track.stop());
    }
    localStream = null;
    localVideo.srcObject = null;
    remoteVideo.srcObject = null;
    remoteStream = null;
    remoteSoundOn = false;
    document.body.classList.remove('has-remote');
}

function mediaConstraints() {
    let mic = '';
    let cam = '';
    try {
        mic = localStorage.getItem('koko.mic') || '';
        cam = localStorage.getItem('koko.cam') || '';
    } catch (error) {}
    return {
        audio: mic ? { deviceId: { exact: mic } } : true,
        video: cam ? { deviceId: { exact: cam }, facingMode: 'user' } : { facingMode: 'user' },
    };
}

async function ensureMedia() {
    if (localStream) return;
    try {
        try {
            localStream = await navigator.mediaDevices.getUserMedia(mediaConstraints());
        } catch (error) {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
        }
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
    if (cameraBtn) label(cameraBtn, 'Camera', false);
}

function resetPeerState() {
    offerApplied = false;
    answerApplied = false;
    lastOfferSdp = '';
    lastAnswerSdp = '';
    remoteSoundOn = false;
    cursorHold = false;
    iceRestarts = 0;
    restartingNow = false;
    publishing = false;
    mediaReadySent = false;
    clearTimeout(failureTimer);
    failureTimer = null;
    signalVersion = 0;
    holdLocalCandidates = false;
    heldLocal = [];
    pendingCandidates = [];
    cursor = 0;
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
    if (!window.RoomState) return;
    const map = {
        'Allow camera and microphone': 'permissions',
        'Finding someone...': 'searching',
        'Connecting...': 'connecting',
        'Connected': 'live',
        'Connection problem': 'reconnecting',
    };
    if (map[text]) window.RoomState.set(map[text]);
    if (text !== 'Connected' && nextBtn.querySelector('span') && nextBtn.querySelector('span').textContent === 'Confirm') {
        label(nextBtn, 'Next', false);
    }
}

let qualityTimer = null;

function paintQuality(text, bad) {
    const el = document.getElementById('quality');
    if (!el) return;
    el.hidden = text === '';
    el.textContent = text;
    el.classList.toggle('is-bad', !!bad);
}

function watchQuality() {
    clearInterval(qualityTimer);
    qualityTimer = setInterval(sampleQuality, 3000);
    sampleQuality();
}

async function sampleQuality() {
    if (!pc || pc.connectionState !== 'connected') {
        if (mediaReadySent) paintQuality('Reconnecting', true);
        return;
    }
    if (pc.connectionState !== 'connected') return;
    try {
        const stats = await pc.getStats();
        let rtt = null;
        let lost = 0;
        let received = 0;
        stats.forEach((report) => {
            if (report.type === 'candidate-pair' && report.state === 'succeeded' && report.nominated && typeof report.currentRoundTripTime === 'number') {
                rtt = report.currentRoundTripTime;
            }
            if (report.type === 'inbound-rtp' && report.kind === 'video') {
                lost += report.packetsLost || 0;
                received += report.packetsReceived || 0;
            }
        });
        const loss = received > 0 ? lost / (lost + received) : 0;
        if ((rtt !== null && rtt > 0.4) || loss > 0.08) paintQuality('Unstable', true);
        else paintQuality('Good', false);
    } catch (error) {}
}

window.__reacquireMedia = async function () {
    stopMedia();
    await ensureMedia();
    muteBtn.disabled = false;
    cameraReady(true);
    if (!pc || !localStream) return;
    const senders = pc.getSenders();
    localStream.getTracks().forEach((track) => {
        const sender = senders.find((item) => item.track && item.track.kind === track.kind);
        if (sender) sender.replaceTrack(track);
        else pc.addTrack(track, localStream);
    });
};

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

function rememberLocalCandidate(candidate) {
    pushDiag(localCandidateLog, {
        at: new Date().toISOString(),
        connection: diagSerial,
        candidate: candidate.candidate,
        sdpMid: candidate.sdpMid ?? null,
        sdpMLineIndex: candidate.sdpMLineIndex ?? null,
    });
}

function rememberRemoteCandidate(candidate) {
    pushDiag(remoteCandidateLog, {
        at: new Date().toISOString(),
        connection: diagSerial,
        candidate: candidate.candidate,
        sdpMid: candidate.sdpMid ?? null,
        sdpMLineIndex: candidate.sdpMLineIndex ?? null,
    });
}

function rememberIceError(event) {
    pushDiag(iceErrors, {
        at: new Date().toISOString(),
        connection: diagSerial,
        errorCode: event && event.errorCode != null ? event.errorCode : null,
        errorText: (event && (event.errorText || event.message)) || '',
        url: (event && event.url) || '',
        address: (event && event.address) || '',
        port: event && event.port != null ? event.port : null,
    });
}

function pushDiag(list, entry) {
    list.push(entry);
    if (list.length > 500) list.shift();
}

function plainStat(stat) {
    const recordedAt = new Date().toISOString();
    try {
        const copy = JSON.parse(JSON.stringify(stat, function (key, value) {
            return typeof value === 'bigint' ? value.toString() : value;
        }));
        copy.recordedAt = recordedAt;
        return copy;
    } catch (error) {
        return {
            recordedAt: recordedAt,
            id: stat && stat.id ? stat.id : '',
            type: stat && stat.type ? stat.type : '',
            error: 'This stat could not be copied.',
        };
    }
}

async function readStats(connection) {
    const report = await connection.getStats();
    const rows = [];
    report.forEach(function (stat) {
        rows.push(plainStat(stat));
    });
    return rows;
}

function selectedPair(stats) {
    const byId = new Map(stats.map(function (stat) { return [stat.id, stat]; }));
    let pair = stats.find(function (stat) {
        return stat.type === 'candidate-pair' && (stat.selected || stat.nominated) && stat.state === 'succeeded';
    });
    if (!pair) {
        const transport = stats.find(function (stat) {
            return stat.type === 'transport' && stat.selectedCandidatePairId;
        });
        if (transport) pair = byId.get(transport.selectedCandidatePairId) || null;
    }
    if (!pair) return null;
    return {
        at: pair.recordedAt,
        pair: pair,
        localCandidate: byId.get(pair.localCandidateId) || null,
        remoteCandidate: byId.get(pair.remoteCandidateId) || null,
    };
}

function rtpByKind(stats, type) {
    const rows = stats.filter(function (stat) {
        return stat.type === type && (stat.kind === 'audio' || stat.kind === 'video' || stat.mediaType === 'audio' || stat.mediaType === 'video');
    });
    return {
        audio: rows.filter(function (stat) { return stat.kind === 'audio' || stat.mediaType === 'audio'; }),
        video: rows.filter(function (stat) { return stat.kind === 'video' || stat.mediaType === 'video'; }),
    };
}

function loggedCandidates(list, serial) {
    return list.filter(function (item) { return item.connection === serial; });
}

async function describeConnection(connection) {
    const at = new Date().toISOString();
    const snapshot = {
        at: at,
        iceConnectionState: connection.iceConnectionState,
        connectionState: connection.connectionState,
        signalingState: connection.signalingState,
        iceGatheringState: connection.iceGatheringState,
        localCandidates: loggedCandidates(localCandidateLog, diagSerial),
        remoteCandidates: loggedCandidates(remoteCandidateLog, diagSerial),
        selectedCandidatePair: null,
        inbound: { audio: [], video: [] },
        outbound: { audio: [], video: [] },
        iceErrors: loggedCandidates(iceErrors, diagSerial),
        stats: [],
        error: null,
    };
    try {
        const stats = await readStats(connection);
        snapshot.stats = stats;
        const localFromStats = stats.filter(function (stat) { return stat.type === 'local-candidate'; });
        const remoteFromStats = stats.filter(function (stat) { return stat.type === 'remote-candidate'; });
        if (localFromStats.length) snapshot.localCandidates = localFromStats;
        if (remoteFromStats.length) snapshot.remoteCandidates = remoteFromStats;
        snapshot.selectedCandidatePair = selectedPair(stats);
        snapshot.inbound = rtpByKind(stats, 'inbound-rtp');
        snapshot.outbound = rtpByKind(stats, 'outbound-rtp');
    } catch (error) {
        snapshot.error = error && error.message ? error.message : 'Peer statistics could not be read.';
    }
    return snapshot;
}

function downloadDiagnostics(data) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'webrtc-diagnostics.json';
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 0);
}

async function exportDiagnostics() {
    const exportedAt = new Date().toISOString();
    const button = document.getElementById('export-diagnostics');
    try {
        const active = pc ? [pc] : [];
        if (!active.length) {
            downloadDiagnostics({
                exportedAt: exportedAt,
                connections: [],
                iceErrors: iceErrors.slice(),
                error: 'No active peer connection. Start a call, then export again.',
            });
            return;
        }
        const connections = [];
        for (const connection of active) {
            connections.push(await describeConnection(connection));
        }
        downloadDiagnostics({
            exportedAt: exportedAt,
            connections: connections,
            iceErrors: iceErrors.slice(),
            error: null,
        });
    } catch (error) {
        const message = error && error.message ? error.message : 'Diagnostics could not be collected.';
        try {
            downloadDiagnostics({
                exportedAt: exportedAt,
                connections: [],
                iceErrors: iceErrors.slice(),
                error: message,
            });
        } catch (downloadError) {
            if (button) button.textContent = 'Could not export diagnostics';
        }
    }
}

window.exportWebRtcDiagnostics = exportDiagnostics;

const exportDiagnosticsBtn = document.getElementById('export-diagnostics');
if (exportDiagnosticsBtn) {
    exportDiagnosticsBtn.addEventListener('click', function () {
        exportDiagnostics().catch(function () {
            exportDiagnosticsBtn.textContent = 'Could not export diagnostics';
        });
    });
}

function newToken() {
    if (window.crypto && typeof crypto.randomUUID === 'function') return crypto.randomUUID();
    return 't-' + Math.random().toString(16).slice(2) + Date.now().toString(16);
}
