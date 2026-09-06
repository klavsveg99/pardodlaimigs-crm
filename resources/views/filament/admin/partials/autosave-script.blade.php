<script>
(function () {
    const debounce = {{ (int) ($debounce ?? 1500) }};
    let timer = null;
    let initial = true;

    function pageLivewire() {
        try {
            const form = document.querySelector('form.fi-sc-form, [data-filament-form], form');
            const root = (form && form.closest('[wire\\:id]'))
                || document.querySelector('main [wire\\:id]')
                || document.querySelector('[wire\\:id]');
            if (!root) {
                return null;
            }
            const id = root.getAttribute('wire:id');
            return window.Livewire?.find(id) || null;
        } catch (e) {
            return null;
        }
    }

    function schedule() {
        if (initial) {
            initial = false;
            return;
        }
        clearTimeout(timer);
        timer = setTimeout(() => {
            try {
                const lw = pageLivewire();
                if (lw && typeof lw.call === 'function') {
                    lw.call('autosave');
                }
            } catch (e) {
                // ignore
            }
        }, debounce);
    }

    document.addEventListener('input', schedule, { passive: true, capture: true });
    document.addEventListener('change', schedule, { passive: true, capture: true });
})();
</script>
