(function () {
    const started = Date.now();
    const path = location.pathname + location.search;
    const csrf = document.querySelector('meta[name="csrf-token"]');
    let sent = 0;

    function send() {
        if (!navigator.sendBeacon) return;
        const seconds = Math.min(86400, Math.round((Date.now() - started) / 1000));
        if (seconds === sent) return;
        sent = seconds;
        const body = new FormData();
        if (csrf) body.append('_token', csrf.content);
        body.append('path', path.slice(0, 190));
        body.append('duration', String(seconds));
        navigator.sendBeacon('/analytics/pulse', body);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') send();
    });
    window.addEventListener('pagehide', send);
    setInterval(function () {
        if (document.visibilityState === 'visible') send();
    }, 15000);
})();
