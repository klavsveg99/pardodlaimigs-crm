{{-- Mobilajā skatā e-pasta adreses tiek lauztas pirms @, lai domēns
     vienmēr sāktos jaunā rindā. Br tags tiek ievietots tikai šaurā skatā
     un tiek noņemts, kad logs kļūst platāks. --}}
<script>
    (() => {
        if (window.pdcTableEmailBreak) {
            return;
        }

        window.pdcTableEmailBreak = true;

        let scheduled = false;

        const isMobile = () => window.matchMedia('(max-width: 639px)').matches;

        const splitEmails = () => {
            scheduled = false;

            if (! isMobile()) {
                document.querySelectorAll('.pdc-email-break').forEach((br) => {
                    const parent = br.parentNode;
                    br.remove();
                    // Atgriežoties platā skatā, teksts atkal jāsavieno vienā
                    // mezglā, citādi nākamajā šaurajā skatā @ vairs nav
                    // atrodams kopā ar adreses sākumu.
                    parent?.normalize();
                });

                return;
            }

            document.querySelectorAll('.fi-ta-table td[class*="email"] .fi-ta-cell-content').forEach((content) => {
                if (content.querySelector('.pdc-email-break')) {
                    return;
                }

                const walker = document.createTreeWalker(content, NodeFilter.SHOW_TEXT);
                let node;

                while ((node = walker.nextNode())) {
                    const at = node.nodeValue.indexOf('@');

                    if (at <= 0) {
                        continue;
                    }

                    const domain = node.splitText(at);
                    const br = document.createElement('br');
                    br.className = 'pdc-email-break';
                    domain.parentNode.insertBefore(br, domain);
                }
            });
        };

        const schedule = () => {
            if (scheduled) {
                return;
            }

            scheduled = true;
            window.requestAnimationFrame(splitEmails);
        };

        // Resize/orientation ir jāpārbauda ar nelielu aizturi: media query
        // stāvoklis var tikt atjaunots pēc resize notikuma.
        const reschedule = () => {
            window.setTimeout(schedule, 60);
        };

        window.addEventListener('resize', reschedule, { passive: true });
        document.addEventListener('livewire:navigated', schedule);
        new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });

        schedule();
    })();
</script>
