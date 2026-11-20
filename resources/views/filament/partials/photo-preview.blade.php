<style>
    .pdc-photo-preview {
        position: fixed;
        inset: 0;
        z-index: 100001;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.9);
    }
    .pdc-photo-preview[hidden] {
        display: none !important;
    }
    .pdc-photo-preview img {
        display: block;
        width: auto;
        height: auto;
        max-width: 92vw;
        max-height: 88vh;
        object-fit: contain;
        border-radius: 0.6rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.55);
    }
    .pdc-photo-preview-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, 0.28);
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 1.6rem;
        line-height: 1;
        cursor: pointer;
        backdrop-filter: blur(4px);
    }
    .pdc-photo-preview-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    .pdc-photo-preview-close:focus-visible {
        outline: 2px solid #fff;
        outline-offset: 3px;
    }
</style>

<script>
(() => {
    if (window.pdcPhotoPreview) return;

    let overlay = null;
    let image = null;
    let closeButton = null;
    let lastFocus = null;

    const build = () => {
        overlay = document.createElement('div');
        overlay.id = 'pdc-photo-preview';
        overlay.className = 'pdc-photo-preview';
        overlay.hidden = true;
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Foto pārskats');
        overlay.innerHTML = '<button type="button" class="pdc-photo-preview-close" aria-label="Aizvērt">&times;</button><img alt="">';
        document.body.appendChild(overlay);

        image = overlay.querySelector('img');
        closeButton = overlay.querySelector('button');

        closeButton.addEventListener('click', hide);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) hide();
        });
        document.addEventListener('keydown', (event) => {
            if (overlay && !overlay.hidden && event.key === 'Escape') hide();
        });
    };

    const open = (url, alt = '') => {
        if (!url) return;
        if (!overlay || !document.body.contains(overlay)) build();

        lastFocus = document.activeElement;
        image.src = url;
        image.alt = alt;
        overlay.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        closeButton.focus();
    };

    const hide = () => {
        if (!overlay) return;
        overlay.hidden = true;
        image.src = '';
        image.alt = '';
        document.documentElement.style.overflow = '';
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    };

    window.pdcPhotoPreview = open;

    // Delegated so thumbnails added by Livewire or Alpine work too.
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-pdc-photo]');
        if (!trigger) return;

        event.preventDefault();
        open(
            trigger.getAttribute('data-pdc-photo'),
            trigger.getAttribute('data-pdc-photo-alt') || ''
        );
    });
})();
</script>
