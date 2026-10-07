{{--
    Shared "Sūtīt vēlreiz" confirmation. Shows the last email to the recipient
    (address, subject, content, date) before re-sending.

    Usage from any email button whose state already says "Nosūtīts":
        window.pdcResendConfirm(lastEmail, () => { ...proceed to send/reopen... });
    `lastEmail` = { to, subject, content, sentAt, attachments } | null.
    A missing log falls back to a plain confirmation without details.
--}}
<script>
if (! window.pdcResendConfirm) {
    window.pdcResendConfirm = function (email, onConfirm) {
        email = email || {};

        const overlay = document.createElement('div');
        overlay.setAttribute('style', 'position:fixed;inset:0;z-index:2147483647;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);padding:1rem;');

        const card = document.createElement('div');
        card.setAttribute('style', 'background:#ffffff;border-radius:0.75rem;width:100%;max-width:560px;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.35);');

        const head = document.createElement('div');
        head.setAttribute('style', 'padding:0.9rem 1.1rem;border-bottom:1px solid #e5e7eb;font-weight:700;color:#111827;font-size:0.95rem;');
        head.textContent = 'Nosūtīt vēlreiz?';

        const bodyWrap = document.createElement('div');
        bodyWrap.setAttribute('style', 'padding:1rem 1.1rem;overflow-y:auto;display:flex;flex-direction:column;gap:0.85rem;');

        const row = (label, value, pre) => {
            const wrap = document.createElement('div');
            const lb = document.createElement('div');
            lb.setAttribute('style', 'font-size:0.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.03em;');
            lb.textContent = label;
            const val = document.createElement('div');
            val.setAttribute('style', 'margin-top:0.2rem;font-size:0.85rem;color:#1f2937;line-height:1.5;'
                + (pre ? 'white-space:pre-wrap;max-height:11rem;overflow-y:auto;background:#f9fafb;border:1px solid #eef0f2;border-radius:0.5rem;padding:0.55rem 0.65rem;' : 'overflow-wrap:anywhere;'));
            val.textContent = (value === null || value === undefined || value === '') ? '-' : String(value);
            wrap.appendChild(lb);
            wrap.appendChild(val);
            bodyWrap.appendChild(wrap);
        };

        bodyWrap.appendChild((() => {
            const intro = document.createElement('p');
            intro.setAttribute('style', 'margin:0;font-size:0.82rem;color:#4b5563;line-height:1.5;');
            intro.textContent = 'Šim saņēmējam jau nosūtīts e-pasts. Pārbaudiet pēdējo e-pastu un apstipriniet atkārtotu nosūtīšanu.';
            return intro;
        })());

        row('Saņēmējs', email.to);
        row('Temats', email.subject);
        if (email.sentAt || email.attachments) {
            const meta = [email.sentAt ? 'Nosūtīts: ' + email.sentAt : '', email.attachments ? 'Pielikumi: ' + email.attachments : ''].filter(Boolean).join(' · ');
            row('Pēdējais e-pasts', meta);
        }
        if (email.content) {
            row('Saturs', email.content, true);
        }

        const foot = document.createElement('div');
        foot.setAttribute('style', 'padding:0.9rem 1.1rem;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:flex-end;gap:0.5rem;background:#ffffff;');

        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.setAttribute('style', 'padding:0.45rem 0.75rem;border-radius:0.5rem;background:transparent;color:#374151;border:1px solid #e5e7eb;cursor:pointer;font-size:0.82rem;font-weight:600;');
        cancel.textContent = 'Atcelt';

        const confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.setAttribute('style', 'display:inline-flex;align-items:center;gap:0.4rem;white-space:nowrap;padding:0.5rem 0.95rem;border-radius:0.5rem;background:var(--pdc-primary, #285854);color:#fff;border:1px solid var(--pdc-primary-darker, #285854);cursor:pointer;font-weight:600;font-size:0.84rem;');
        confirm.textContent = 'Sūtīt vēlreiz';

        foot.appendChild(cancel);
        foot.appendChild(confirm);
        card.appendChild(head);
        card.appendChild(bodyWrap);
        card.appendChild(foot);
        overlay.appendChild(card);

        const close = () => {
            overlay.remove();
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
        const onKey = (e) => { if (e.key === 'Escape') close(); };

        cancel.onclick = close;
        overlay.onclick = (e) => { if (e.target === overlay) close(); };
        confirm.onclick = () => {
            close();
            if (typeof onConfirm === 'function') onConfirm();
        };

        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        document.body.appendChild(overlay);
        confirm.focus();
    };
}
</script>
