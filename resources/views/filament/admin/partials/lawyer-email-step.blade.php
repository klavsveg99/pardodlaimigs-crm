@php
    // Jurista dokumenta 2. solis: e-pasts juristam. Tāda pati struktūra un
    // stils kā klienta pielikumu e-pasta modālim, bet pilnīgi patstāvīgs —
    // klienta modālis (filament.partials.attachment-send-popup) netiek aiztikts.
    //
    // Saņēmējs, temats un pamatteksts tiek sagatavoti serverī no 1. soļa
    // datiem; šeit tos tikai parāda un ļauj labot.
    $to = (string) ($to ?? '');
    $subject = (string) ($subject ?? '');
    $bodyHtml = (string) ($bodyHtml ?? '');
    $files = $files ?? [];
    $sendUrl = (string) ($sendUrl ?? '');
    $uploadUrl = (string) ($uploadUrl ?? '');
    $deleteUrl = (string) ($deleteUrl ?? '');
@endphp

<div
    data-pdc-lawyer-email
    x-data="{
        to: @js($to),
        subject: @js($subject),
        bodyHtml: @js($bodyHtml),
        info: '',
        files: @js($files),
        selected: [],
        uploading: false,
        sending: false,
        error: '',
        sent: false,
        sendUrl: @js($sendUrl),
        uploadUrl: @js($uploadUrl),
        deleteUrl: @js($deleteUrl),
        csrf: null,
        init() {
            this.csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
        },
        get preview() {
            const extra = (this.info || '').trim();
            const block = extra === '' ? '' : '<p>' + extra.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>') + '</p>';
            return this.bodyHtml + block;
        },
        isSel(id) { return this.selected.includes(id); },
        toggle(id) {
            const i = this.selected.indexOf(id);
            if (i === -1) { this.selected.push(id); } else { this.selected.splice(i, 1); }
        },
        sizeLabel(bytes) {
            if (! bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1).replace('.0', '') + ' MB';
        },
        totalSelected() {
            return this.files.filter(f => this.selected.includes(f.id)).reduce((s, f) => s + (f.size || 0), 0);
        },
        async onPick(event) {
            const list = Array.from(event.target.files || []);
            event.target.value = '';
            if (! list.length) return;
            this.error = '';
            this.uploading = true;
            try {
                for (const file of list) {
                    const fd = new FormData();
                    fd.append('file', file);
                    const resp = await fetch(this.uploadUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    const data = await resp.json().catch(() => ({}));
                    if (! resp.ok || ! data.id) {
                        this.error = data.message || data.error || ('Neizdevās augšupielādēt: ' + file.name);
                        continue;
                    }
                    this.files.push(data);
                    this.selected.push(data.id);
                }
            } catch (e) {
                this.error = 'Kļūda augšupielādē: ' + (e.message || 'unknown');
            } finally {
                this.uploading = false;
            }
        },
        async submit() {
            if (this.sending) return;
            this.error = '';
            if (! this.to || ! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.to)) { this.error = 'Norādiet derīgu e-pasta adresi.'; return; }
            if (! this.selected.length) { this.error = 'Izvēlieties vismaz vienu failu.'; return; }
            if (! this.subject.trim()) { this.error = 'Norādiet tematu.'; return; }
            if (this.totalSelected() > 18 * 1024 * 1024) { this.error = 'Pielikumi pārsniedz 18 MB — izvēlieties mazāk failu.'; return; }
            this.sending = true;
            try {
                const resp = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ to: this.to, subject: this.subject, files: this.selected, body: this.bodyHtml, extra_info: this.info || '' }),
                });
                const data = await resp.json().catch(() => ({}));
                if (! resp.ok || ! data.ok) { this.error = data.message || 'Nosūtīšana neizdevās. Pārbaudiet saņēmēja adresi un failu izmēru.'; return; }
                this.sent = true;
            } catch (e) {
                this.error = 'Kļūda: ' + (e.message || 'unknown');
            } finally {
                this.sending = false;
            }
        },
    }"
>
    {{-- 1. solis --}}
    <div x-show="! sent" style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div style="display: grid; gap: 0.65rem;">
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Saņēmējs</span>
                <input type="email" x-model="to" placeholder="e-pasts" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827;" />
            </label>
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Temats</span>
                <input type="text" x-model="subject" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827;" />
            </label>
        </div>

        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem;">
                <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Faili <span style="color: #6b7280; font-weight: 500;" x-text="'(kopā ' + sizeLabel(totalSelected()) + ')'"></span></span>
                <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 600; color: var(--pdc-primary, #285854); cursor: pointer; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.3rem 0.6rem;">
                    <svg style="width: 0.85rem; height: 0.85rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span x-text="uploading ? 'Augšupielādē…' : 'Pievienot failu'"></span>
                    <input type="file" multiple x-on:change="onPick($event)" style="display: none;" />
                </label>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.35rem; border: 1px solid #e5e7eb; border-radius: 0.6rem; padding: 0.65rem; max-height: 190px; overflow-y: auto;">
                <template x-for="f in files" :key="f.id">
                    <label style="display: flex; align-items: center; gap: 0.55rem; cursor: pointer; padding: 0.25rem 0.15rem; border-radius: 0.35rem;">
                        <input type="checkbox" x-bind:checked="isSel(f.id)" x-on:change="toggle(f.id)" style="accent-color: var(--pdc-primary, #285854); height: 1rem; width: 1rem; cursor: pointer;" />
                        <span style="font-size: 0.82rem; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="f.name"></span>
                        <span style="font-size: 0.72rem; color: #6b7280;" x-text="sizeLabel(f.size)"></span>
                    </label>
                </template>
                <template x-if="! files.length">
                    <span style="font-size: 0.82rem; color: #6b7280;">Nav pievienotu failu.</span>
                </template>
            </div>
        </div>

        <div>
            <span style="font-size: 0.8rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.35rem;">E-pasta priekšskatījums</span>
            <div x-html="preview" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.75rem 0.8rem; font-size: 0.85rem; line-height: 1.55; color: #1f2937; background: #fff; max-height: 20rem; overflow-y: auto;"></div>
        </div>

        <label style="display: flex; flex-direction: column; gap: 0.25rem;">
            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Papildus informācija</span>
            <textarea x-model="info" rows="3" placeholder="Ja vajag, pievieno papildu informāciju — tā tiks pievienota e-pasta beigās." style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; width: 100%; background: #fff; color: #111827; resize: vertical;"></textarea>
        </label>

        <div x-show="error" x-cloak style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.5rem 0.7rem; border-radius: 0.45rem; font-size: 0.8rem;" x-text="error"></div>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
            <span style="font-size: 0.7rem; color: #9ca3af; line-height: 1.4;">Nosūtītāja e-pasts: info@pardodlaimigs.lv · pielikumu limits 18 MB</span>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <button type="button" x-on:click="$dispatch('pdc-lawyer-prev')" style="padding: 0.45rem 0.75rem; border-radius: 0.5rem; background: transparent; color: #374151; border: 1px solid #e5e7eb; cursor: pointer; font-size: 0.82rem; font-weight: 600;">Atpakaļ</button>
                <button type="button" x-on:click="submit()" :disabled="sending"
                    style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: var(--pdc-primary, #285854); color: #fff; border: 1px solid var(--pdc-primary-darker, #285854); cursor: pointer; font-weight: 600; font-size: 0.84rem; opacity: sending ? 0.7 : 1;">
                    <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                    <span x-text="sending ? 'Nosūta...' : 'Nosūtīt'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Pēc nosūtīšanas --}}
    <div x-show="sent" x-cloak style="display: flex; flex-direction: column; align-items: center; gap: 0.9rem; padding: 1.5rem 0.5rem; text-align: center;">
        <p style="font-size: 0.95rem; font-weight: 600; color: #111827; margin: 0;">E-pasts nosūtīts uz <span x-text="to"></span></p>
        <button type="button" x-on:click="$dispatch('pdc-lawyer-done')" style="padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: var(--pdc-primary, #285854); color: #fff; border: 1px solid var(--pdc-primary-darker, #285854); cursor: pointer; font-weight: 600; font-size: 0.84rem;">Aizvērt</button>
    </div>
</div>
