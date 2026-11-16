{{-- Mobilajā skatā e-pasta adreses tiek lauztas pirms @, lai domēns
     vienmēr sāktos jaunā rindā. Br tags tiek ievietots tikai šaurā skatā
     un tiek noņemts, kad logs kļūst platāks. --}}
<script>
    (() => {
        if (window.pdcTableEmailBreak) {
            return;
        }

        window.pdcTableEmailBreak = true;

        const mobile = window.matchMedia('(max-width: 639px)');
        let scheduled = false;

        const splitEmails = () => {
            scheduled = false;

            if (! mobile.matches) {
                document.querySelectorAll('.pdc-email-break').forEach((br) => br.remove());

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

        mobile.addEventListener('change', reschedule);
        window.addEventListener('resize', reschedule, { passive: true });
        document.addEventListener('livewire:navigated', schedule);
        new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });

        schedule();
    })();
</script>
