(function () {
    const names = ['permissions', 'searching', 'connecting', 'live', 'reconnecting', 'partner-left', 'ended'];

    function set(name) {
        if (!names.includes(name)) return;
        document.body.dataset.room = name;
        names.forEach((item) => document.body.classList.remove('room-' + item));
        document.body.classList.add('room-' + name);
    }

    window.RoomState = { set: set };
    set('permissions');
})();
