@php
    // "Jurista dokuments" 2. solis: saņēmējs, nosūtītājs, temats un
    // priekšskatījuma poga. Saņēmējs nāk no izvēlētā jurista (izpilditājs),
    // tāpēc to parāda uzreiz; pilno e-pastu ielādē tikai pēc pogas.
    $jurists = $jurists ?? [];
    $juristId = (string) ($juristId ?? '');
    $fromAddress = (string) ($fromAddress ?? '');
    $fromName = (string) ($fromName ?? '');
@endphp

<div
    x-data="{
        jurists: @js($jurists),
        juristId: @js($juristId),
        loading: false,
        open: false,
        error: '',
        preview: { to: '', jurist: '', subject: '', html: '' },
        init() {
            this.juristId = @js($juristId);
        },
        get component() {
            if (this.$wire && typeof this.$wire.call === 'function') { return this.$wire; }
            if (window.Livewire) {
                const c = window.Livewire.all().find(c => /crm-property/.test(c.name));
                if (c && c.$wire) { return c.$wire; }
            }
            return null;
        },
        get jurist() { return this.jurists[this.juristId] || null; },
        get to() { return this.preview.to || (this.jurist ? this.jurist.email : ''); },
        get juristName() { return this.preview.jurist || (this.jurist ? this.jurist.name : ''); },
        async openPreview() {
            this.error = '';
            this.loading = true;
            try {
                const c = this.component;
                if (! c) { this.error = 'Priekšskatījumu neizdevās ielādēt.'; return; }
                const extra = c.get('mountedActions.0.data.extra_info') || '';
                const p = await c.call('previewLawyerEmail', extra);
                if (! p) { this.error = 'Priekšskatījumu neizdevās ielādēt.'; return; }
                this.preview = p;
                this.open = true;
            } catch (e) {
                this.error = 'Priekšskatījumu neizdevās ielādēt.';
            } finally {
                this.loading = false;
            }
        },
    }"
    x-on:keydown.escape.window="open = false"
>
    <div style="font-size: 0.82rem; color: #4b5563; line-height: 1.7;">
        <div><strong>Saņēmējs:</strong> <span x-text="to || '—'"></span><template x-if="juristName"><span x-text="' (' + juristName + ')'"></span></template></div>
        <div><strong>Nosūtītājs:</strong> {{ $fromAddress }}@if ($fromName !== '') ({{ $fromName }})@endif</div>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-top: 0.5rem;">
        <span style="font-size: 0.82rem; color: #b91c1c;" x-show="error" x-text="error"></span>
        <button type="button" x-on:click="openPreview()" :disabled="loading"
            style="margin-left: auto; display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.45rem 0.8rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; font-size: 0.82rem; font-weight: 600; opacity: loading ? 0.7 : 1;">
            <svg style="width: 0.95rem; height: 0.95rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span x-text="loading ? 'Ielādē…' : 'Priekšskatījums'"></span>
        </button>
    </div>

    <template x-if="open">
        <div style="position: fixed; inset: 0; z-index: 2147483646; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); padding: 1rem;" x-on:click.self="open = false">
            <div style="background: #ffffff; border-radius: 0.75rem; width: 100%; max-width: 720px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.35);">
                <div style="padding: 0.9rem 1.1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <span style="font-weight: 700; color: #111827; font-size: 0.95rem;">E-pasta priekšskatījums</span>
                    <button type="button" x-on:click="open = false" title="Aizvērt" style="height: 2rem; width: 2rem; border-radius: 9999px; color: #374151; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;">
                        <svg style="width: 1rem; height: 1rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div style="padding: 1rem 1.1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="font-size: 0.82rem; color: #4b5563; line-height: 1.6;">
                        <div><strong>Kam:</strong> <span x-text="preview.to || '—'"></span><template x-if="preview.jurist"><span x-text="' (' + preview.jurist + ')'"></span></template></div>
                        <div><strong>Nosūtītājs:</strong> {{ $fromAddress }}@if ($fromName !== '') ({{ $fromName }})@endif</div>
                        <div><strong>Temats:</strong> <span x-text="preview.subject || '—'"></span></div>
                        <div><strong>Pielikumi:</strong> <span x-text="(preview.attachments && preview.attachments.length) ? preview.attachments.join(', ') : 'nav'"></span></div>
                    </div>
                    <iframe title="E-pasta priekšskatījums" x-bind:srcdoc="preview.html"
                        style="width: 100%; height: 34rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #ffffff;"></iframe>
                </div>
                <div style="padding: 0.9rem 1.1rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; background: #ffffff;">
                    <button type="button" x-on:click="open = false" style="padding: 0.45rem 0.75rem; border-radius: 0.5rem; background: transparent; color: #374151; border: 1px solid #e5e7eb; cursor: pointer; font-size: 0.82rem; font-weight: 600;">Aizvērt</button>
                </div>
            </div>
        </div>
    </template>
</div>
