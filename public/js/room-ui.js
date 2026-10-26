(function () {
    const wrap = document.getElementById('local-wrap');
    const form = document.getElementById('pref-form');
    const summary = document.getElementById('pref-summary');
    const csrf = document.querySelector('meta[name="csrf-token"]');
    const mute = document.getElementById('mute');
    const camera = document.getElementById('camera');
    const localState = document.getElementById('local-state');

    if (wrap && window.matchMedia('(pointer: fine)').matches) {
        let drag = null;
        wrap.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            const rect = wrap.getBoundingClientRect();
            drag = { dx: event.clientX - rect.left, dy: event.clientY - rect.top };
            wrap.setPointerCapture(event.pointerId);
        });
        wrap.addEventListener('pointermove', (event) => {
            if (!drag) return;
            const x = Math.min(window.innerWidth - wrap.offsetWidth - 12, Math.max(12, event.clientX - drag.dx));
            const y = Math.min(window.innerHeight - wrap.offsetHeight - 12, Math.max(12, event.clientY - drag.dy));
            wrap.style.left = x + 'px';
            wrap.style.top = y + 'px';
            wrap.style.right = 'auto';
            wrap.style.bottom = 'auto';
        });
        wrap.addEventListener('pointerup', () => { drag = null; });
    }

    if (form && csrf) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const body = {
                gender_preference: form.gender_preference.value,
                country_preference: form.country_preference.value,
            };
            const response = await fetch('/preferences', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf.content,
                },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            if (response.ok && summary && data.summary) summary.textContent = data.summary;
        });
    }

    function paintLocal() {
        if (!localState || !mute || !camera) return;
        const bits = [];
        if (mute.textContent.includes('Unmute') || mute.getAttribute('aria-pressed') === 'true') bits.push('Muted');
        if (camera.textContent.includes('off') || camera.getAttribute('aria-pressed') === 'true') bits.push('Camera off');
        localState.textContent = bits.join(' · ');
    }

    if (mute) mute.addEventListener('click', () => setTimeout(paintLocal, 0));
    if (camera) camera.addEventListener('click', () => setTimeout(paintLocal, 0));

    const micSelect = document.getElementById('mic-device');
    const camSelect = document.getElementById('cam-device');

    function fillDevices(select, kind, saved) {
        if (!select || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
        navigator.mediaDevices.enumerateDevices().then((devices) => {
            const current = select.value;
            select.replaceChildren();
            const blank = document.createElement('option');
            blank.value = '';
            blank.textContent = 'Default';
            select.append(blank);
            devices.filter((device) => device.kind === kind).forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.textContent = device.label || (kind + ' ' + (index + 1));
                select.append(option);
            });
            select.value = devices.some((device) => device.deviceId === saved) ? saved : current;
        }).catch(() => {});
    }

    let savedMic = '';
    let savedCam = '';
    try {
        savedMic = localStorage.getItem('koko.mic') || '';
        savedCam = localStorage.getItem('koko.cam') || '';
    } catch (error) {}
    fillDevices(micSelect, 'audioinput', savedMic);
    fillDevices(camSelect, 'videoinput', savedCam);

    function remember(key, value) {
        try { localStorage.setItem(key, value); } catch (error) {}
        if (typeof window.__reacquireMedia === 'function') window.__reacquireMedia();
    }
    if (micSelect) micSelect.addEventListener('change', () => remember('koko.mic', micSelect.value));
    if (camSelect) camSelect.addEventListener('change', () => remember('koko.cam', camSelect.value));

    const speaker = document.getElementById('spk-device');
    const speakerLabel = document.getElementById('spk-label');
    const remote = document.getElementById('remote');
    if (speaker && remote && typeof remote.setSinkId === 'function') {
        speaker.hidden = false;
        if (speakerLabel) speakerLabel.hidden = false;
        let savedSpeaker = '';
        try { savedSpeaker = localStorage.getItem('koko.speaker') || ''; } catch (error) {}
        fillDevices(speaker, 'audiooutput', savedSpeaker);
        speaker.addEventListener('change', () => {
            try { localStorage.setItem('koko.speaker', speaker.value); } catch (error) {}
            remote.setSinkId(speaker.value).catch(() => {});
        });
    }

    const desktop = window.matchMedia('(min-width: 761px) and (pointer: fine)');
    document.addEventListener('keydown', (event) => {
        if (!desktop.matches) return;
        const tag = event.target && event.target.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        if (event.metaKey || event.ctrlKey || event.altKey) return;
        const key = event.key.toLowerCase();
        if (key === 'm' && mute) mute.click();
        else if (key === 'v' && camera) camera.click();
        else if (key === 'n') {
            const next = document.getElementById('next');
            if (next) next.click();
        } else if (event.key === 'Escape') {
            const end = document.getElementById('end');
            if (end) end.click();
        } else return;
        event.preventDefault();
    });
})();
