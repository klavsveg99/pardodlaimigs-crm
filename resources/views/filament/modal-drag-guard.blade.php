<script>
// Filament closes a modal on `x-on:click.self` of `.fi-modal-window-ctn`.
// A press that starts inside the modal window (e.g. selecting text in an input)
// and is released outside it makes the browser dispatch the click on the
// backdrop container, which looked identical to a real click-away and closed
// the modal. Only let the click-away through when both the press and the
// release happened on the backdrop itself; otherwise swallow it before
// Alpine's close handler runs. Close button, Escape and real backdrop clicks
// keep working as before.
(function () {
    var pressTarget = null;
    var releaseTarget = null;
    var isBackdrop = function (node) {
        return !!node && !!node.classList && node.classList.contains('fi-modal-window-ctn');
    };

    document.addEventListener('mousedown', function (event) {
        pressTarget = event.target;
    }, true);

    document.addEventListener('mouseup', function (event) {
        releaseTarget = event.target;
    }, true);

    document.addEventListener('click', function (event) {
        if (!isBackdrop(event.target)) {
            return;
        }

        if (pressTarget === event.target && releaseTarget === event.target) {
            return;
        }

        event.stopImmediatePropagation();
        event.preventDefault();
    }, true);
})();
</script>
