(function () {
    const toggle = document.getElementById('menu-toggle');
    const side = document.querySelector('.side');
    if (toggle && side) {
        toggle.addEventListener('click', function () {
            const open = side.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    document.addEventListener('submit', function (event) {
        const message = event.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) event.preventDefault();
    });

    document.querySelectorAll('form[data-dirty]').forEach(function (form) {
        let dirty = false;
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (event) {
            if (!dirty) return;
            event.preventDefault();
            event.returnValue = '';
        });
    });
})();
