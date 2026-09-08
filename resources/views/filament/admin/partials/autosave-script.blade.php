<div id="pdc-autosave-marker" hidden data-debounce="{{ (int) ($debounce ?? 1500) }}"></div>
<script>
(function () {
    // Attach once — Livewire SPA navigation re-executes this script on every
    // edit page visit, but document-level listeners from previous visits persist.
    if (window.__pdcAutosaveAttached) {
        return;
    }
    window.__pdcAutosaveAttached = true;

    var timer = null;
    var initial = true;

    function debounceMs() {
        var marker = document.getElementById('pdc-autosave-marker');
        var d = marker ? parseInt(marker.getAttribute('data-debounce') || '1500', 10) : 1500;
        return isNaN(d) ? 1500 : d;
    }

    // Only autosave when we are actually on an edit/create page that opted in.
    function onAutosavePage() {
        return !!document.getElementById('pdc-autosave-marker');
    }

    // Ignore keystrokes inside table search / filters / relation managers /
    // topbar / sidebar — autosave must only react to the main resource form.
    function insideMainForm(target) {
        if (!target || !target.closest) {
            return false;
        }
        // Table toolbars, filters and relation-manager tables never trigger autosave.
        if (target.closest('[data-filament-table], .fi-ta-header-toolbar, .fi-ta-filters, .fi-table-filters')) {
            return false;
        }
        // Topbar (global search) and sidebar must never trigger autosave —
        // calling `autosave` on those components 500s (MethodNotFoundException).
        if (target.closest('.fi-topbar, .fi-sidebar, [data-filament-topbar], [data-filament-sidebar]')) {
            return false;
        }
        var form = target.closest('main form');
        if (!form) {
            return false;
        }
        // Main Filament schema form carries one of these markers.
        if (form.classList.contains('fi-sc-form') || form.hasAttribute('data-filament-form') || form.querySelector('[data-field-wrapper]')) {
            return true;
        }
        // Fallback: the first (main) form on an edit page, not a nested
        // relation-manager component form.
        var forms = Array.prototype.slice.call(document.querySelectorAll('main form'));
        return forms.length > 0 && forms[0] === form;
    }

    function schedule(e) {
        if (initial) {
            initial = false;
            return;
        }
        if (!onAutosavePage()) {
            return;
        }
        if (e && e.target && !insideMainForm(e.target)) {
            return;
        }
        clearTimeout(timer);
        // `change` (selects like Pieteikuma avots) also triggers a Livewire `live()`
        // round-trip — wait a bit longer so autosave never overlaps it
        // (overlapping requests caused snapshot-mismatch page errors).
        var wait = debounceMs();
        if (e && e.type === 'change') {
            wait = Math.max(wait, 2500);
        }
        timer = setTimeout(function () {
            if (!onAutosavePage()) {
                return;
            }
            try {
                // NOTE: dispatched as a global Livewire EVENT, not a direct
                // method call. Only the edit page (AutosavesForm trait) listens
                // for `pdc-autosave-tick`; every other component (topbar,
                // sidebar, tables, relation managers) ignores it harmlessly.
                // A direct `call('autosave')` on a mis-targeted component
                // throws MethodNotFoundException → 500 (seen in production).
                if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                    window.Livewire.dispatch('pdc-autosave-tick');
                }
            } catch (err) {
                // ignore — next keystroke will retry
            }
        }, wait);
    }

    // Reset the "ignore first event" guard on every full Livewire navigation.
    document.addEventListener('livewire:navigated', function () {
        initial = true;
        clearTimeout(timer);
    });

    document.addEventListener('input', schedule, { passive: true, capture: true });
    document.addEventListener('change', schedule, { passive: true, capture: true });
})();
</script>
