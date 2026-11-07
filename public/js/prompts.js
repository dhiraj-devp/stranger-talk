(function () {
    var node = document.getElementById('prompt-data');
    var out = document.getElementById('prompt-text');
    var category = document.getElementById('prompt-category');
    var next = document.getElementById('prompt-next');
    var copy = document.getElementById('prompt-copy');
    if (!node || !out || !next) return;

    var data;
    try {
        data = JSON.parse(node.textContent || '{}');
    } catch (error) {
        return;
    }

    var last = -1;

    function pool() {
        var key = category ? category.value : 'all';
        if (!key || key === 'all') return data.flat || [];
        return (data.groups && data.groups[key]) || data.flat || [];
    }

    function show() {
        var items = pool();
        if (!items.length) return;
        var index = Math.floor(Math.random() * items.length);
        if (items.length > 1 && index === last) index = (index + 1) % items.length;
        last = index;
        out.textContent = items[index];
    }

    next.addEventListener('click', show);
    if (category) {
        category.addEventListener('change', function () {
            last = -1;
            show();
        });
    }
    if (copy) {
        copy.addEventListener('click', function () {
            var text = out.textContent || '';
            if (!text || !navigator.clipboard) return;
            navigator.clipboard.writeText(text).then(function () {
                copy.textContent = 'Copied';
                window.setTimeout(function () {
                    copy.textContent = 'Copy';
                }, 1200);
            });
        });
    }
})();
