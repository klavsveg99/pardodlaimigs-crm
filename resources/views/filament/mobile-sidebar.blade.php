{{-- Mobilā izvēlne: aizvēršanas poga galvenē un aizvēršana ar pirksta
     vilkšanu pa kreisi. Poga ir redzama tikai sānjoslas galvenē, kas
     Filament mobilajā skatā ir vienīgā vieta ar brīvu labo stūri. --}}
<button
    type="button"
    class="pdc-sidebar-close-btn"
    aria-label="Aizvērt izvēlni"
    title="Aizvērt izvēlni"
    x-on:click="$store.sidebar.close()"
>
    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
</button>

<script>
    (() => {
        if (window.pdcSidebarSwipe) {
            return;
        }

        window.pdcSidebarSwipe = true;

        let startX = null;
        let startY = null;

        document.addEventListener('pointerdown', (event) => {
            if (! event.target.closest('#fi-main-sidebar')) {
                return;
            }

            if (! window.Alpine?.store('sidebar')?.isOpen) {
                return;
            }

            startX = event.clientX;
            startY = event.clientY;
        }, { passive: true });

        document.addEventListener('pointerup', (event) => {
            if (startX === null) {
                return;
            }

            const dx = event.clientX - startX;
            const dy = event.clientY - startY;

            startX = null;
            startY = null;

            // Īss horizontāls vilciens pa kreisi aizver izvēlni; vertikāla
            // ritināšana paliek neskarta.
            if (dx < -60 && Math.abs(dx) > Math.abs(dy) * 1.4) {
                window.Alpine?.store('sidebar')?.close();
            }
        }, { passive: true });

        document.addEventListener('pointercancel', () => {
            startX = null;
            startY = null;
        }, { passive: true });
    })();
</script>
