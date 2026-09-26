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
})();
