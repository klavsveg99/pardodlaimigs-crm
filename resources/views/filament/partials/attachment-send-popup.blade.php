@php
    // Self-contained attachment email popup. Opened via the window event
    // `pdc-open-send` with detail { file: {id,name}, all: [{id,name,size}] }.
    $clients = $clients ?? [];
    $defaultTo = (string) ($defaultTo ?? '');
    $baseWa = (string) ($baseWa ?? '');
    $fromName = $fromName ?? (auth()->user()?->name ?: (config('mail.from.name') ?: 'Pārdod Laimīgs'));
    // Explicit endpoint; legacy fallback from mode/slug kept for the
    // property view page include.
    $sendUrl = $sendUrl ?? ($mode === 'property'
        ? route('properties.attachments.send-email', ['propertySlug' => $slug ?? ''])
        : route('clients.attachments.send-email', ['clientSlug' => $slug ?? '']));
    $defaultBody = '<p>Sveiki,</p><p>pievienoju saistītos failus.</p><p>Ar cieņu,<br>Pārdod Laimīgs</p>';
@endphp

<div
    data-pdc-send-popup
    x-data="{
        open: false,
        sending: false,
        error: '',
        sendUrl: @js($sendUrl),
        clients: @js($clients),
        defaultTo: @js($defaultTo),
        baseWa: @js($baseWa),
        fromName: @js($fromName),
        target: null,
        candidates: [],
        selected: [],
        to: '',
        subject: '',
        clientId: null,
        init() {},
        openWith(detail) {
            if (! detail || ! detail.file) return;
            this.target = detail.file;
            this.candidates = detail.all && detail.all.length ? detail.all : [detail.file];
            this.selected = [detail.file.id];
            this.subject = detail.file.name || '';
            this.error = '';
            if (this.clients.length > 0) {
                const first = this.clients.find(c => c.email) || this.clients[0] || null;
                this.clientId = first ? first.id : null;
                this.to = first ? (first.email || '') : '';
            } else {
                this.clientId = null;
                this.to = this.defaultTo;
            }
            this.open = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (this.$refs.editor && ! this.$refs.editor.innerHTML.trim()) {
                    this.$refs.editor.innerHTML = @js($defaultBody);
                }
            });
        },
        close() {
            if (this.sending) return;
            this.open = false;
            this.target = null;
            document.body.style.overflow = '';
        },
        isSel(id) { return this.selected.includes(id); },
        toggle(id) {
            const i = this.selected.indexOf(id);
            if (i === -1) { this.selected.push(id); }
            else if (this.selected.length > 1) { this.selected.splice(i, 1); }
        },
        sizeLabel(bytes) {
            if (! bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1).replace('.0', '') + ' MB';
        },
        totalSelected() {
            return this.candidates
                .filter(c => this.selected.includes(c.id))
                .reduce((sum, c) => sum + (c.size || 0), 0);
        },
        get waLink() {
            if (this.clients.length > 0) {
                const c = this.clientId ? this.clients.find(x => x.id === this.clientId) : null;
                const digits = String((c && c.phone) || '').replace(/\D+/g, '');
                if (digits.length === 8) return 'https://wa.me/371' + digits;
                return digits ? 'https://wa.me/' + digits : '';
            }
            return this.baseWa || '';
        },
        clientChanged() {
            const c = this.clients.find(x => x.id === this.clientId);
            if (c && c.email) { this.to = c.email; }
        },
        execCmd(cmd, value) {
            this.$refs.editor && this.$refs.editor.focus();
            document.execCommand(cmd, false, value || null);
        },
        async submit() {
            if (this.sending || ! this.target) return;
            this.error = '';
            if (! this.to || ! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.to)) { this.error = 'Norādiet derīgu e-pasta adresi.'; return; }
            if (! this.selected.length) { this.error = 'Izvēlieties vismaz vienu failu.'; return; }
            if (! this.subject.trim()) { this.error = 'Norādiet tematu.'; return; }
            if (this.totalSelected() > 18 * 1024 * 1024) { this.error = 'Pielikumi pārsniedz 18 MB — izvēlieties mazāk failu.'; return; }
            this.sending = true;
            try {
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                const resp = await fetch(this.sendUrl, {
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
                        from_name: this.fromName,
                        client_id: this.clientId,
                        files: this.selected,
                        body: this.$refs.editor ? this.$refs.editor.innerHTML : '',
                    }),
                });
                const data = await resp.json().catch(() => ({}));
                if (! resp.ok || ! data.ok) {
                    this.error = data.message || 'Nosūtīšana neizdevās. Pārbaudiet saņēmēja adresi un failu izmēru.';
                    return;
                }
                const sent = { marked: data.marked || [], sentAt: data.sentAt || '' };
                window.dispatchEvent(new CustomEvent('pdc-attachment-sent', { detail: sent }));
                const to = this.to;
                this.open = false;
                this.target = null;
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
    x-on:pdc-open-send.window="openWith($event.detail)"
    x-on:keydown.escape.window="open && close()"
>
    <template x-if="open">
        <div
            style="position: fixed; inset: 0; z-index: 2147483647; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); padding: 1rem;"
            x-on:click.self="close()"
        >
            <div style="background: #ffffff; border-radius: 0.75rem; width: 100%; max-width: 640px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.35);">
                <div style="padding: 0.9rem 1.1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <span style="font-weight: 700; color: #111827; font-size: 0.95rem;">Nosūtīt failus ar e-pastu</span>
                    <button type="button" x-on:click="close()" title="Aizvērt" style="height: 2rem; width: 2rem; border-radius: 9999px; background: rgba(255,255,255,0.08); color: #374151; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;">
                        <svg style="width: 1rem; height: 1rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="padding: 1rem 1.1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: grid; gap: 0.65rem;">
                        <label x-bind:style="{ display: (clients.length > 1) ? 'flex' : 'none' }" style="display: none; flex-direction: column; gap: 0.25rem;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Klients</span>
                            <select x-model.number="clientId" x-on:change="clientChanged()" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827;">
                                <template x-for="c in clients" :key="c.id">
                                    <option :value="c.id" x-text="c.name + (c.email ? ' · ' + c.email : '')"></option>
                                </template>
                            </select>
                        </label>
                        <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Saņēmējs</span>
                            <input type="email" x-model="to" placeholder="e-pasts" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827;" />
                        </label>
                        <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Sūtītāja vārds</span>
                            <div style="display: flex; align-items: stretch; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; background: #fff;">
                                <input type="text" x-model="fromName" style="border: 0; padding: 0.45rem 0.6rem; font-size: 0.875rem; flex: 1 1 auto; min-width: 0; background: transparent; color: #111827; outline: none;" />
                                <span style="display: inline-flex; align-items: center; padding: 0 0.6rem; font-size: 0.78rem; color: #6b7280; background: #f9fafb; border-left: 1px solid #e5e7eb; white-space: nowrap;">info@pardodlaimigs.lv</span>
                            </div>
                        </label>
                        <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Temats</span>
                            <input type="text" x-model="subject" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827;" />
                        </label>
                    </div>

                    <div>
                        <span style="font-size: 0.8rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.35rem;">Faili <span style="color: #6b7280; font-weight: 500;" x-text="'(kopā ' + sizeLabel(totalSelected()) + ')'"></span></span>
                        <div style="display: flex; flex-direction: column; gap: 0.35rem; border: 1px solid #e5e7eb; border-radius: 0.6rem; padding: 0.65rem; max-height: 170px; overflow-y: auto;">
                            <template x-for="f in candidates" :key="f.id">
                                <label style="display: flex; align-items: center; gap: 0.55rem; cursor: pointer; padding: 0.25rem 0.15rem; border-radius: 0.35rem;">
                                    <input type="checkbox" x-bind:checked="isSel(f.id)" x-on:change="toggle(f.id)" style="accent-color: var(--pdc-primary, #285854); height: 1rem; width: 1rem; cursor: pointer;" />
                                    <span style="font-size: 0.82rem; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="f.name"></span>
                                    <span style="font-size: 0.72rem; color: #6b7280;" x-text="sizeLabel(f.size)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; align-items: center; gap: 0.25rem; flex-wrap: wrap; border: 1px solid #e5e7eb; border-bottom: none; border-radius: 0.5rem 0.5rem 0 0; padding: 0.35rem 0.5rem; background: #f9fafb;">
                            <button type="button" x-on:click="execCmd('bold')" title="Treknraksts" style="border: 0; background: transparent; cursor: pointer; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-weight: 700; color: #374151; font-size: 0.82rem;">B</button>
                            <button type="button" x-on:click="execCmd('italic')" title="Slīpraksts" style="border: 0; background: transparent; cursor: pointer; color: #374151; font-size: 0.82rem; font-style: italic; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-family: Georgia, serif;">I</button>
                            <button type="button" x-on:click="execCmd('underline')" title="Pasvītrojums" style="border: 0; background: transparent; cursor: pointer; color: #374151; text-decoration: underline; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.82rem;">U</button>
                            <button type="button" x-on:click="execCmd('strikeThrough')" title="Pārsvītrojums" style="border: 0; background: transparent; cursor: pointer; color: #374151; text-decoration: line-through; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.82rem;">S</button>
                            <button type="button" x-on:click="execCmd('insertUnorderedList')" title="Nenumurēts saraksts" style="border: 0; background: transparent; cursor: pointer; color: #374151; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.85rem;">•</button>
                            <button type="button" x-on:click="execCmd('insertOrderedList')" title="Numurēts saraksts" style="border: 0; background: transparent; cursor: pointer; color: #374151; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.8rem;">1.</button>
                            <button type="button" x-on:click="execCmd('insertHorizontalRule')" title="Horizontāla līnija" style="border: 0; background: transparent; cursor: pointer; color: #374151; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.82rem;">—</button>
                            <button type="button" x-on:click="execCmd('formatBlock','<blockquote>')" title="Citāts" style="border: 0; background: transparent; cursor: pointer; color: #374151; padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-size: 0.82rem; font-family: Georgia, serif;">„ ”</button>
                        </div>
                        <div x-ref="editor" contenteditable="true"
                            style="min-height: 7rem; padding: 0.7rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0 0 0.5rem 0.5rem; font-size: 0.85rem; line-height: 1.55; overflow-y: auto; max-height: 16rem; background: #fff; color: #1f2937; outline: none;"></div>
                        <div style="margin-top: 0.3rem; font-size: 0.7rem; color: #9ca3af; line-height: 1.4;">Nosūtītāja e-pasts: info@pardodlaimigs.lv · pielikumu limits 18 MB</div>
                    </div>

                    <div x-show="error" x-cloak style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.5rem 0.7rem; border-radius: 0.45rem; font-size: 0.8rem;" x-text="error"></div>
                </div>

                <div style="padding: 0.9rem 1.1rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; background: #ffffff;">
                    <a x-bind:style="{ display: waLink ? 'inline-flex' : 'none' }" :href="waLink" target="_blank" rel="noopener"
                        style="display: inline-flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 0.4rem; white-space: nowrap; color: #1da851; text-decoration: none; font-weight: 600; font-size: 0.82rem; padding: 0.45rem 0.75rem; border: 1px solid #bbe7c8; border-radius: 0.5rem;">
                        <svg style="width: 1.05rem; height: 1.05rem; flex: none;" fill="currentColor" viewBox="0 0 448 512" aria-hidden="true"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
                        <span>Atvērt WhatsApp čatu</span>
                    </a>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <button type="button" x-on:click="close()" style="padding: 0.45rem 0.75rem; border-radius: 0.5rem; background: transparent; color: #374151; border: 1px solid #e5e7eb; cursor: pointer; font-size: 0.82rem; font-weight: 600;">Atcelt</button>
                        <button type="button" x-on:click="submit()" :disabled="sending"
                            style="display: inline-flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 0.4rem; white-space: nowrap; padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: var(--pdc-primary, #285854); color: #fff; border: 1px solid var(--pdc-primary-darker, #285854); cursor: pointer; font-weight: 600; font-size: 0.84rem; opacity: sending ? 0.7 : 1;">
                            <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                            <span x-text="sending ? 'Nosūta...' : 'Nosūtīt'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
