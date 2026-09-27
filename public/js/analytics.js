(function () {
    var node = document.currentScript;
    if (!node) return;
    window.dataLayer = window.dataLayer || [];
    var gtm = node.getAttribute('data-gtm');
    var ga = node.getAttribute('data-ga');
    if (gtm) {
        window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
    }
    if (ga) {
        function gtag() { window.dataLayer.push(arguments); }
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', ga);
    }
})();
