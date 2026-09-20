<script>
// Filament modals close on `x-on:click.self` of `.fi-modal-window-ctn`, and the
// app's custom Alpine modals (attachment editor/lightbox, send popup, AI panel,
// avatar editor, property lightbox) use their own `x-on:click.self` backdrops.
// A press that starts inside the modal (e.g. selecting text in an input) and is
// released outside it makes the browser dispatch the click on the backdrop,
// which looked identical to a real click-away and closed the modal. Only let a
// click-away through when both the press and the release happened on the
// backdrop itself; otherwise swallow the click before Alpine's handler runs.
// Close button, Escape and real backdrop clicks keep working as before.
(function () {
    var pressTarget = null;
    var releaseTarget = null;

    function isClickAwayTarget(node) {
        if (!node || node.nodeType !== 1) {
            return false;
        }
        if (node.classList && node.classList.contains('fi-modal-window-ctn')) {
            return true;
        }
        // Alpine leaves x-on:click.self / @click.self on the element it binds to.
        if (node.attributes) {
            for (var i = 0; i < node.attributes.length; i++) {
                if (node.attributes[i].name.indexOf('click.self') !== -1) {
                    return true;
                }
            }
        }
        return false;
    }

    document.addEventListener('mousedown', function (event) {
        pressTarget = event.target;
    }, true);

    document.addEventListener('mouseup', function (event) {
        releaseTarget = event.target;
    }, true);

    document.addEventListener('click', function (event) {
        if (!isClickAwayTarget(event.target)) {
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
