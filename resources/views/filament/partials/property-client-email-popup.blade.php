@php
    // "Paldies par sadarbību" e-pasts saistītajam klientam. Atverams ar
    // notikumu `pdc-open-client-email` (detail {id, name, email, url}).
    $defaultSubject = 'Paldies par sadarbību';
    $defaultBody = '<p>Sveiki,</p>'
        .'<p>Pateicamies par uzticēšanos Jūsu īpašuma pārdošanā.</p>'
        .'<p>Novērtēsim, ja atstāsiet atsauksmi par mūsu sadarbību šeit:<br>'
        .'<a href="https://g.page/r/CRZd6XZu2hhmEBM/review">https://g.page/r/CRZd6XZu2hhmEBM/review</a></p>'
        .'<p>Jūsu nekustamā īpašuma birojs,<br>Pārdod Laimīgs</p>';
@endphp

<div
    data-pdc-client-email-popup
    x-data="{
        open: false,
        sending: false,
        error: '',
        client: null,
        to: '',
        subject: @js($defaultSubject),
        init() {},
        openWith(detail) {
            if (! detail) return;
            this.client = detail;
            this.to = detail.email || '';
            this.subject = @js($defaultSubject);
            this.error = '';
            this.open = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (this.$refs.editor) {
                    this.$refs.editor.innerHTML = @js($defaultBody);
                }
            });
        },
        close() {
            if (this.sending) return;
            this.open = false;
            document.body.style.overflow = '';
        },
        execCmd(cmd, value) {
            this.$refs.editor && this.$refs.editor.focus();
            document.execCommand(cmd, false, value || null);
        },
        async submit() {
            if (this.sending || ! this.client) return;
            this.error = '';
            if (! this.to || ! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.to)) { this.error = 'Norādiet derīgu e-pasta adresi.'; return; }
            if (! this.subject.trim()) { this.error = 'Norādiet tematu.'; return; }
            this.sending = true;
            try {
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                const resp = await fetch(this.client.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        to: this.to,
                        subject: this.subject,
                        body: this.$refs.editor ? this.$refs.editor.innerHTML : '',
                    }),
                });
                const data = await resp.json().catch(() => ({}));
                if (! resp.ok || ! data.ok) {
                    this.error = data.message || 'Nosūtīšana neizdevās.';
                    return;
                }
                const to = this.to;
                this.open = false;
                this.client = null;
                document.body.style.overflow = '';
                const note = document.createElement('div');
                note.textContent = 'E-pasts nosūtīts uz ' + to;
                note.setAttribute('style', 'position:fixed;top:1rem;right:1rem;z-index:2147483647;background:#16a34a;color:#fff;padding:0.6rem 1rem;border-radius:0.5rem;font-weight:600;font-size:0.85rem;box-shadow:0 8px 24px rgba(0,0,0,0.25);');
                document.body.appendChild(note);
                setTimeout(() => note.remove(), 4000);
            } catch (e) {
                this.error = 'Kļūda: ' + (e.message || 'unknown');
            } finally {
                this.sending = false;
            }
        },
    }"
    x-on:pdc-open-client-email.window="openWith($event.detail)"
    x-on:keydown.escape.window="open && close()"
>
    <template x-if="open">
        <div
            style="position: fixed; inset: 0; z-index: 2147483647; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); padding: 1rem;"
            x-on:click.self="close()"
        >
            <div style="background: #ffffff; border-radius: 0.75rem; width: 100%; max-width: 640px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.35);">
                <div style="padding: 0.9rem 1.1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <span style="font-weight: 700; color: #111827; font-size: 0.95rem;">Paldies par sadarbību</span>
                    <button type="button" x-on:click="close()" title="Aizvērt" style="height: 2rem; width: 2rem; border-radius: 9999px; background: rgba(255,255,255,0.08); color: #374151; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;">
                        <svg style="width: 1rem; height: 1rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="padding: 1rem 1.1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem;">
                    <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Saņēmējs</span>
                        <input type="email" x-model="to" placeholder="e-pasts" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827;" />
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Temats</span>
                        <input type="text" x-model="subject" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827;" />
                    </label>

                    <div>
                        <div style="display: flex; align-items: center; gap: 0.25rem; flex-wrap: wrap; border: 1px solid #e5e7eb; border-bottom: none; border-radius: 0.5rem 0.5rem 0 0; padding: 0.35rem 0.5rem; background: #f9fafb;">
                            <button type="button" x-on:click="execCmd('bold')" title="Treknraksts" style="border: 0; background: transparent; cursor: pointer; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-weight: 700; color: #374151; font-size: 0.82rem;">B</button>
                            <button type="button" x-on:click="execCmd('italic')" title="Slīpraksts" style="border: 0; background: transparent; cursor: pointer; color: #374151; font-size: 0.82rem; font-style: italic; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-family: Georgia, serif;">I</button>
                            <button type="button" x-on:click="execCmd('underline')" title="Pasvītrojums" style="border: 0; background: transparent; cursor: pointer; color: #374151; text-decoration: underline; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.82rem;">U</button>
                            <button type="button" x-on:click="execCmd('insertUnorderedList')" title="Nenumurēts saraksts" style="border: 0; background: transparent; cursor: pointer; color: #374151; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.85rem;">•</button>
                            <button type="button" x-on:click="execCmd('createLink', prompt('Saite:', 'https://') || null)" title="Saite" style="border: 0; background: transparent; cursor: pointer; color: #374151; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.82rem;">🔗</button>
                        </div>
                        <div x-ref="editor" contenteditable="true"
                            style="min-height: 9rem; padding: 0.7rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0 0 0.5rem 0.5rem; font-size: 0.85rem; line-height: 1.55; overflow-y: auto; max-height: 20rem; background: #fff; color: #1f2937; outline: none;"></div>
                        <div style="margin-top: 0.3rem; font-size: 0.7rem; color: #9ca3af; line-height: 1.4;">Nosūtītāja e-pasts: info@pardodlaimigs.lv</div>
                    </div>

                    <div x-show="error" x-cloak style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.5rem 0.7rem; border-radius: 0.45rem; font-size: 0.8rem;" x-text="error"></div>
                </div>

                <div style="padding: 0.9rem 1.1rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; background: #ffffff;">
                    <button type="button" x-on:click="close()" style="padding: 0.45rem 0.75rem; border-radius: 0.5rem; background: transparent; color: #374151; border: 1px solid #e5e7eb; cursor: pointer; font-size: 0.82rem; font-weight: 600;">Atcelt</button>
                    <button type="button" x-on:click="submit()" :disabled="sending"
                        style="display: inline-flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 0.4rem; white-space: nowrap; padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: var(--pdc-primary, #285854); color: #fff; border: 1px solid var(--pdc-primary-darker, #285854); cursor: pointer; font-weight: 600; font-size: 0.84rem; opacity: sending ? 0.7 : 1;">
                        <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                        <span x-text="sending ? 'Nosūta...' : 'Nosūtīt'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
