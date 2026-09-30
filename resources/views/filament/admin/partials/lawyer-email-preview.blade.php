<div style="display: flex; flex-direction: column; gap: 0.75rem;">
    <div style="font-size: 0.82rem; color: #4b5563; line-height: 1.55;">
        <div><strong>Kam:</strong> {{ $to !== '' ? $to : '—' }}@if ($jurist !== '') ({{ $jurist }})@endif</div>
        <div><strong>Temats:</strong> {{ $subject !== '' ? $subject : '—' }}</div>
        @if (($attachments ?? 0) > 0)
            <div><strong>Pielikumi:</strong> {{ $attachments }}</div>
        @endif
    </div>

    {{-- E-pasts tiek attēlots iframe, lai inline stili un fons izskatītos
         tieši tāpat kā klienta e-pasta programmā. --}}
    <iframe
        title="E-pasta priekšskatījums"
        srcdoc="{{ $html }}"
        style="width: 100%; height: 34rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #ffffff;"
    ></iframe>
</div>
