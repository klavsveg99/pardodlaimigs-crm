@php
    // 2. soļa galvene klienta e-pasta modāļa stilā: saņēmējs (jurists) un
    // temats tikai lasāmi. Jurists tiek izvēlēts 1. solī, tāpēc saņēmēju
    // parāda no juristu saraksta pēc izvēlētā id (bez servera apgrieziena).
    $jurists = $jurists ?? [];
    $juristId = (string) ($juristId ?? '');
@endphp

<div
    x-data="{
        jurists: @js($jurists),
        juristId: @js($juristId),
        init() {
            const read = () => {
                const el = document.getElementById('mountedActionSchema0.jurist_id');
                const m = el && el.getAttribute('wire:model');
                if (! m) { return; }
                const wire = (this.$wire && typeof this.$wire.get === 'function')
                    ? this.$wire
                    : (window.Livewire ? (window.Livewire.all().find(c => /crm-property/.test(c.name)) || {}).$wire : null);
                if (wire && typeof wire.get === 'function') {
                    this.juristId = String(wire.get(m) ?? '');
                }
            };
            this.$nextTick(read);
            document.addEventListener('livewire:updated', read);
            read();
        },
        get jurist() { return this.jurists[this.juristId] || null; },
    }"
    style="display: grid; gap: 0.65rem;"
>
    <label style="display: flex; flex-direction: column; gap: 0.25rem;">
        <span style="font-size: 0.8rem; font-weight: 600; color: #374151;">Saņēmējs</span>
        <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.45rem 0.6rem; font-size: 0.875rem; background: #f9fafb; color: #111827;"
            x-text="jurist ? (jurist.email + ' (' + jurist.name + ')') : '—'"></div>
    </label>
</div>
