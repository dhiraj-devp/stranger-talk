(function () {
    const panel = document.getElementById('chat-panel');
    const toggle = document.getElementById('chat-toggle');
    const log = document.getElementById('chat-log');
    const empty = document.getElementById('chat-empty');
    const errorEl = document.getElementById('chat-error');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const send = document.getElementById('chat-send');
    const unread = document.getElementById('chat-unread');
    const close = document.getElementById('chat-close');
    const report = document.getElementById('chat-report');
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!panel || !toggle || !log || !form || !input || !send || !csrf) return;

    let token = '';
    let live = false;
    let after = 0;
    let generation = 0;
    let timer = null;
    let misses = 0;
    let sending = false;
    const seen = new Set();

    let unreadCount = 0;

    function setUnread(count) {
        unreadCount = count;
        if (!unread) return;
        unread.hidden = count < 1;
        unread.textContent = String(count);
    }

    function open(next) {
        panel.hidden = !next;
        document.body.classList.toggle('chat-open', next);
        toggle.setAttribute('aria-expanded', next ? 'true' : 'false');
        if (next) {
            setUnread(0);
            if (live) input.focus();
            stick(true);
        }
    }

    function bumpUnread() {
        if (!panel.hidden) return;
        setUnread(unreadCount + 1);
    }

    function clearConversation() {
        generation += 1;
        clearTimeout(timer);
        after = 0;
        misses = 0;
        seen.clear();
        log.replaceChildren();
        if (empty) empty.hidden = false;
        if (errorEl) errorEl.hidden = true;
        setUnread(0);
    }

    function setLive(next) {
        live = next;
        input.disabled = !next;
        send.disabled = !next || sending;
        input.placeholder = next ? 'Message' : 'Waiting for a match';
        if (!next) clearTimeout(timer);
        else pull();
    }

    function stick(force) {
        const near = log.scrollHeight - log.scrollTop - log.clientHeight < 48;
        if (force || near) log.scrollTop = log.scrollHeight;
    }

    function stamp(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function append(message, pending) {
        const key = message.client_id || ('id:' + message.id);
        if (seen.has(key) && !pending) {
            const existing = log.querySelector('[data-key="' + CSS.escape(key) + '"]');
            if (existing) existing.classList.remove('is-sending');
            return false;
        }
        if (seen.has(key)) return false;
        seen.add(key);
        const item = document.createElement('div');
        item.className = 'bubble' + (message.mine ? ' is-mine' : '') + (pending ? ' is-sending' : '');
        item.dataset.key = key;
        const text = document.createElement('span');
        text.textContent = message.body;
        const time = document.createElement('time');
        time.dateTime = message.at || '';
        time.textContent = pending ? 'Sending' : stamp(message.at);
        item.append(text, time);
        log.append(item);
        if (empty) empty.hidden = true;
        return true;
    }

    async function pull() {
        const ticket = generation;
        const current = token;
        if (!live || !current) return;
        try {
            const response = await fetch('/match/messages?token=' + encodeURIComponent(current) + '&after=' + after, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (ticket !== generation || current !== token) return;
            if (response.status === 409 || response.status === 404) {
                setLive(false);
                return;
            }
            if (!response.ok) throw new Error('Messages are not updating.');
            const data = await response.json();
            (data.messages || []).forEach((message) => {
                const added = append(message, false);
                if (added && !message.mine) bumpUnread();
                after = Math.max(after, Number(message.id) || 0);
            });
            if (typeof data.cursor === 'number') after = Math.max(after, data.cursor);
            misses = 0;
            if (errorEl) errorEl.hidden = true;
            stick(false);
        } catch (error) {
            if (ticket !== generation) return;
            misses += 1;
            if (misses >= 3 && errorEl) {
                errorEl.hidden = false;
                errorEl.textContent = 'Messages are not updating. The video call is unchanged.';
            }
        }
        if (ticket === generation && live) timer = setTimeout(pull, 1000);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!live || sending) return;
        const body = input.value.replace(/^\s+|\s+$/g, '');
        if (!body) return;
        const clientId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now());
        sending = true;
        send.disabled = true;
        append({ client_id: clientId, body: body, mine: true, at: new Date().toISOString() }, true);
        stick(true);
        input.value = '';
        try {
            const response = await fetch('/match/messages', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf.content,
                },
                body: JSON.stringify({ token: token, client_id: clientId, body: body }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Message was not sent.');
            const row = log.querySelector('[data-key="' + CSS.escape(clientId) + '"]');
            if (row) {
                row.classList.remove('is-sending');
                const time = row.querySelector('time');
                if (time && data.message) {
                    time.dateTime = data.message.at || '';
                    time.textContent = stamp(data.message.at);
                }
            }
            if (data.message) after = Math.max(after, Number(data.message.id) || 0);
            if (errorEl) errorEl.hidden = true;
        } catch (error) {
            seen.delete(clientId);
            const row = log.querySelector('[data-key="' + CSS.escape(clientId) + '"]');
            if (row) row.remove();
            input.value = body;
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.textContent = error.message || 'Message was not sent.';
            }
        } finally {
            sending = false;
            send.disabled = !live;
        }
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            if (form.requestSubmit) form.requestSubmit();
            else form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
    });

    toggle.addEventListener('click', () => open(panel.hidden));
    if (close) close.addEventListener('click', () => open(false));
    if (report) {
        report.addEventListener('click', () => {
            const button = document.getElementById('report');
            if (button) button.click();
        });
    }

    document.addEventListener('koko:match', (event) => {
        const detail = event.detail || {};
        const nextToken = detail.token || '';
        const status = detail.status || 'idle';
        if (status === 'reset' || status === 'ended' || status === 'replaced' || (nextToken && nextToken !== token)) clearConversation();
        token = nextToken;
        const nextLive = status === 'connecting' || status === 'connected';
        if (nextLive !== live) setLive(nextLive);
        else if (!nextLive) setLive(false);
    });
})();
