@php
    // Pēdējais solis tāpat kā klienta pielikumu e-pasta modālis: saņēmējs,
    // temats, pielikumi (ar tukšu stāvokli un iespēju pievienot failu) un
    // rediģējams saturs. Priekšskatījums atveras tikai pēc pogas nospiešanas.
    $to = (string) ($to ?? '');
    $juristName = (string) ($jurist ?? '');
    $subject = (string) ($subject ?? '');
    $body = (string) ($body ?? '');
    $html = (string) ($html ?? '');
    $candidates = $candidates ?? [];
    $uploadUrl = (string) ($uploadUrl ?? '');
    $deleteUrl = (string) ($deleteUrl ?? '');
    $fromAddress = (string) config('mail.from.address');
    $fromName = (string) config('mail.from.name');
    $maxBytes = \App\Services\Mail\EmailSender::MAX_ATTACHMENT_BYTES;
    $csrf = csrf_token();
    // Servera vērtības Alpine komponentei: Livewire morph atjaunina DOM, bet
    // x-data paliek neskarts, tāpēc komponente datus pārlasa no šī JSON.
    $uid = 'pdc-lawyer-mail-'.\Illuminate\Support\Str::random(6);
    $payload = [
        'to' => $to,
        'jurist' => $juristName,
        'subject' => $subject,
        'body' => $body,
        'html' => $html,
        'candidates' => $candidates,
    ];
@endphp

<script type="application/json" id="{{ $uid }}-data">{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

<div
    x-data="{
        to: '',
        jurist: '',
        subject: '',
        bodyHtml: '',
        previewHtml: '',
        candidates: [],
        selected: [],
        sending: false,
        error: '',
        previewOpen: false,
        uploadUrl: @js($uploadUrl),
        deleteUrl: @js($deleteUrl),
        csrf: @js($csrf),
        uploading: [],
        maxBytes: {{ $maxBytes }},
        syncFromServer() {
            let d = {};
            try { d = JSON.parse(document.getElementById('{{ $uid }}-data').textContent) || {}; } catch (e) { d = {}; }
            const prevSelected = this.selected;
            this.to = d.to || '';
            this.jurist = d.jurist || '';
            this.subject = d.subject || '';
            this.bodyHtml = d.body || '';
            this.previewHtml = d.html || '';
            this.candidates = d.candidates || [];
            // Saglabā lietotāja atzīmes, ja tās ir; citādi atzīmē visus.
            const ids = this.candidates.map(c => c.id);
            this.selected = prevSelected.filter(id => ids.includes(id));
            if (this.selected.length === 0) { this.selected = ids.slice(); }
            this.$nextTick(() => {
                if (this.$refs.editor && this.$refs.editor.innerHTML.trim() === '') {
                    this.$refs.editor.innerHTML = this.bodyHtml;
                }
            });
        },
        init() {
            this.syncFromServer();
            if (! this._boundUpdated) {
                this._boundUpdated = true;
                document.addEventListener('livewire:updated', () => {
                    if (this.$el && this.$el.isConnected) { this.syncFromServer(); }
                });
            }
        },
        isSel(id) { return this.selected.includes(id); },
        toggle(id) {
            const i = this.selected.indexOf(id);
            if (i === -1) { this.selected.push(id); }
            else { this.selected.splice(i, 1); }
            this.pushState();
        },
        pushState() {
            this.$wire.set('data.attachment_ids', this.selected, false);
            this.$wire.set('data.email_body', this.$refs.editor ? this.$refs.editor.innerHTML : '', false);
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
        selectedNames() {
            return this.candidates.filter(c => this.selected.includes(c.id)).map(c => c.name);
        },
        execCmd(cmd, value) {
            this.$refs.editor && this.$refs.editor.focus();
            document.execCommand(cmd, false, value || null);
        },
        pickFiles() { this.$refs.fileInput && this.$refs.fileInput.click(); },
        async handleUpload(e) {
            const input = e.target;
            const files = Array.from(input.files || []);
            input.value = '';
            for (const file of files) {
                const track = { id: 'up-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7), name: file.name };
                this.uploading.push(track);
                try {
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('_token', this.csrf);
                    const resp = await fetch(this.uploadUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    const data = await resp.json().catch(() => ({}));
                    if (! resp.ok || ! data.id) {
                        this.error = data.message || ('Augšupielāde neizdevās: ' + file.name);
                        continue;
                    }
                    this.candidates.push({ id: data.id, name: data.name, size: data.size, source: 'Īpašums' });
                    this.selected.push(data.id);
                    this.pushState();
                } catch (err) {
                    this.error = 'Augšupielāde neizdevās: ' + (err.message || file.name);
                } finally {
                    this.uploading = this.uploading.filter(u => u.id !== track.id);
                }
            }
        },
        async removeOne(id) {
            if (! this.deleteUrl) return;
            if (! confirm('Dzēst šo failu?')) return;
            try {
                const resp = await fetch(this.deleteUrl.replace(':id', String(id)), {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (! resp.ok) return;
            } catch (e) { return; }
            this.candidates = this.candidates.filter(c => c.id !== id);
            this.selected = this.selected.filter(s => s !== id);
            this.pushState();
        },
        openPreview() {
            this.error = '';
            if (! this.to || ! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.to)) { this.error = 'Norādiet derīgu e-pasta adresi.'; return; }
            this.pushState();
            this.previewOpen = true;
            this.$nextTick(() => this.refreshPreview());
        },
        // Priekšskatījums rāda rediģēto saturu, tāpēc iframe saturs tiek
        // pārbūvē no redaktora HTML (bez atkārtota servera pieprasījuma).
        refreshPreview() {
            if (! this.$refs.preview || ! this.$refs.editor) return;
            const raw = this.bodyHtml;
            const wrapper = this.previewHtml;
            if (raw && wrapper.includes(raw)) {
                this.$refs.preview.srcdoc = wrapper.replace(raw, this.$refs.editor.innerHTML);
            } else {
                this.$refs.preview.srcdoc = this.$refs.editor.innerHTML;
            }
        },
        submit() {
            this.error = '';
            if (! this.to || ! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.to)) { this.error = 'Norādiet derīgu e-pasta adresi.'; return; }
            if (! this.subject.trim()) { this.error = 'Norādiet tematu.'; return; }
            if (this.totalSelected() > this.maxBytes) { this.error = 'Pielikumi pārsniedz 18 MB — izvēlieties mazāk failu.'; return; }
            this.sending = true;
            this.pushState();
            this.$wire.call('callMountedAction').finally(() => { this.sending = false; });
        },
    }"
    style="display: flex; flex-direction: column; gap: 0.75rem;"
    x-on:keydown.escape.window="previewOpen = false"
>
    <div>
        <div style="display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 0.5rem;">
            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Saņēmējs</span>
            <div style="font-size: 0.875rem; color: #111827; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; background: #f9fafb;">
                {{ $to !== '' ? $to : '—' }}@if ($juristName !== '') ({{ $juristName }})@endif
            </div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem;">
            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Pielikumi <span style="color: #6b7280; font-weight: 500;" x-text="'(kopā ' + sizeLabel(totalSelected()) + ')'"></span></span>
            <button type="button" x-on:click="pickFiles()" style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.6rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; font-size: 0.78rem; font-weight: 600;">
                <svg style="width: 0.9rem; height: 0.9rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
                <span>Pievienot failu</span>
            </button>
        </div>
        <input type="file" x-ref="fileInput" multiple style="display: none;" x-on:change="handleUpload($event)" />
        <div style="display: flex; flex-direction: column; gap: 0.35rem; border: 1px solid #e5e7eb; border-radius: 0.6rem; padding: 0.65rem; max-height: 200px; overflow-y: auto; min-height: 3rem;">
            <div x-show="candidates.length === 0 && uploading.length === 0" style="font-size: 0.82rem; color: #6b7280;">Nav pievienotu failu. Pievienojiet failu ar pogu "Pievienot failu".</div>
            <template x-for="f in candidates" :key="f.id">
                <label style="display: flex; align-items: center; gap: 0.55rem; cursor: pointer; padding: 0.25rem 0.15rem; border-radius: 0.35rem;">
                    <input type="checkbox" x-bind:checked="isSel(f.id)" x-on:change="toggle(f.id)" style="accent-color: var(--pdc-primary, #285854); height: 1rem; width: 1rem; cursor: pointer;" />
                    <span style="font-size: 0.82rem; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="f.name"></span>
                    <span style="font-size: 0.72rem; color: #6b7280;" x-text="sizeLabel(f.size)"></span>
                    <button type="button" x-on:click.prevent="removeOne(f.id)" title="Dzēst" style="margin-left: auto; border: 0; background: transparent; color: #9ca3af; cursor: pointer; padding: 0.1rem 0.3rem;">&times;</button>
                </label>
            </template>
            <template x-for="u in uploading" :key="u.id">
                <div style="display: flex; align-items: center; gap: 0.55rem; padding: 0.25rem 0.15rem; font-size: 0.82rem; color: #6b7280;">
                    <span x-text="u.name"></span>
                    <span>augšupielādē…</span>
                </div>
            </template>
        </div>
    </div>

    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem;">
            <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">E-pasta saturs</span>
            <button type="button" x-on:click="openPreview()" style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.6rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; font-size: 0.78rem; font-weight: 600;">
                <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Priekšskatījums</span>
            </button>
        </div>
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
        <div x-ref="editor" contenteditable="true" x-on:input="pushState()"
            style="min-height: 12rem; padding: 0.7rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0 0 0.5rem 0.5rem; font-size: 0.85rem; line-height: 1.55; overflow-y: auto; max-height: 24rem; background: #fff; color: #1f2937; outline: none;"></div>
        <div style="margin-top: 0.3rem; font-size: 0.7rem; color: #9ca3af; line-height: 1.4;">Nosūtītāja e-pasts: {{ $fromAddress }} · pielikumu limits 18 MB</div>
    </div>

    <div x-show="error" x-cloak style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.5rem 0.7rem; border-radius: 0.45rem; font-size: 0.8rem;" x-text="error"></div>

    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
        <button type="button" x-on:click="submit()" :disabled="sending"
            style="display: inline-flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 0.4rem; white-space: nowrap; padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: var(--pdc-primary, #285854); color: #fff; border: 1px solid var(--pdc-primary-darker, #285854); cursor: pointer; font-weight: 600; font-size: 0.84rem; opacity: sending ? 0.7 : 1;">
            <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
            <span x-text="sending ? 'Nosūta...' : 'Nosūtīt'"></span>
        </button>
    </div>

    {{-- Priekšskatījuma modālis: saņēmējs, nosūtītājs, pielikumi un pilns e-pasts. --}}
    <template x-if="previewOpen">
        <div style="position: fixed; inset: 0; z-index: 2147483646; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); padding: 1rem;" x-on:click.self="previewOpen = false">
            <div style="background: #ffffff; border-radius: 0.75rem; width: 100%; max-width: 720px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.35);">
                <div style="padding: 0.9rem 1.1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <span style="font-weight: 700; color: #111827; font-size: 0.95rem;">E-pasta priekšskatījums</span>
                    <button type="button" x-on:click="previewOpen = false" title="Aizvērt" style="height: 2rem; width: 2rem; border-radius: 9999px; background: rgba(255,255,255,0.08); color: #374151; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;">
                        <svg style="width: 1rem; height: 1rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div style="padding: 1rem 1.1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="font-size: 0.82rem; color: #4b5563; line-height: 1.6;">
                        <div><strong>Kam:</strong> <span x-text="to || '—'"></span><template x-if="jurist"><span x-text="' (' + jurist + ')'"></span></template></div>
                        <div><strong>Nosūtītājs:</strong> {{ $fromAddress }}@if ($fromName !== '') ({{ $fromName }})@endif</div>
                        <div><strong>Temats:</strong> <span x-text="subject || '—'"></span></div>
                        <div><strong>Pielikumi (<span x-text="selected.length"></span>):</strong>
                            <template x-if="selected.length === 0"><span> nav</span></template>
                            <ul x-show="selected.length > 0" style="margin: 0.15rem 0 0 1.1rem; padding: 0;">
                                <template x-for="n in selectedNames()" :key="n"><li x-text="n"></li></template>
                            </ul>
                        </div>
                    </div>
                    <iframe x-ref="preview" title="E-pasta priekšskatījums"
                        style="width: 100%; height: 34rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #ffffff;"></iframe>
                </div>
                <div style="padding: 0.9rem 1.1rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; background: #ffffff;">
                    <button type="button" x-on:click="previewOpen = false" style="padding: 0.45rem 0.75rem; border-radius: 0.5rem; background: transparent; color: #374151; border: 1px solid #e5e7eb; cursor: pointer; font-size: 0.82rem; font-weight: 600;">Aizvērt</button>
                    <button type="button" x-on:click="previewOpen = false; submit()" :disabled="sending"
                        style="padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: var(--pdc-primary, #285854); color: #fff; border: 1px solid var(--pdc-primary-darker, #285854); cursor: pointer; font-weight: 600; font-size: 0.84rem; opacity: sending ? 0.7 : 1;">
                        <span x-text="sending ? 'Nosūta...' : 'Nosūtīt'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
