@php
    // Tiesi priekšskatījuma poga un modālis. Saņēmējs/temats/pielikumi ir
    // atsevišķi Filament lauki; šeit tikai parāda pilnu e-pastu.
    $to = (string) ($to ?? '');
    $juristName = (string) ($jurist ?? '');
    $subject = (string) ($subject ?? '');
    $html = (string) ($html ?? '');
    $fromAddress = (string) config('mail.from.address');
    $fromName = (string) config('mail.from.name');
@endphp

<div
    x-data="{
        open: false,
        to: @js($to),
        jurist: @js($juristName),
        subject: @js($subject),
        html: @js($html),
        names: [],
        openPreview() {
            // Pielikumu nosaukumus nolasa no atzīmētajiem CheckboxList laukiem.
            this.names = [...document.querySelectorAll('input[type=checkbox][id^=\"mountedActionSchema\"][id*=\"attachment_ids\"]')]
                .filter(cb => cb.checked)
                .map(cb => cb.closest('label')?.innerText.trim() || '')
                .filter(Boolean);
            this.open = true;
        },
    }"
    x-on:keydown.escape.window="open = false"
>
    <div style="display: flex; justify-content: flex-end;">
        <button type="button" x-on:click="openPreview()"
            style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.45rem 0.8rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; font-size: 0.82rem; font-weight: 600;">
            <svg style="width: 0.95rem; height: 0.95rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Priekšskatījums</span>
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
                        <div><strong>Kam:</strong> <span x-text="to || '—'"></span><template x-if="jurist"><span x-text="' (' + jurist + ')'"></span></template></div>
                        <div><strong>Nosūtītājs:</strong> {{ $fromAddress }}@if ($fromName !== '') ({{ $fromName }})@endif</div>
                        <div><strong>Temats:</strong> <span x-text="subject || '—'"></span></div>
                        <div><strong>Pielikumi (<span x-text="names.length"></span>):</strong> <span x-text="names.length ? names.join(', ') : 'nav'"></span></div>
                    </div>
                    <iframe title="E-pasta priekšskatījums" srcdoc="{{ $html }}"
                        style="width: 100%; height: 34rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #ffffff;"></iframe>
                </div>
                <div style="padding: 0.9rem 1.1rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; background: #ffffff;">
                    <button type="button" x-on:click="open = false" style="padding: 0.45rem 0.75rem; border-radius: 0.5rem; background: transparent; color: #374151; border: 1px solid #e5e7eb; cursor: pointer; font-size: 0.82rem; font-weight: 600;">Aizvērt</button>
                </div>
            </div>
        </div>
    </template>
</div>
