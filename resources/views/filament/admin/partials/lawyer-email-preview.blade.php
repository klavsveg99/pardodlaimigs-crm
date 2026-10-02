@php
    // E-pasta priekšskatījuma saturs (Filament modālis): saņēmējs,
    // nosūtītājs, pielikumi un pilns e-pasts.
    $preview = $preview ?? [];
    $fromAddress = (string) ($fromAddress ?? '');
    $fromName = (string) ($fromName ?? '');
    $to = (string) ($preview['to'] ?? '');
    $juristName = (string) ($preview['jurist'] ?? '');
    $subject = (string) ($preview['subject'] ?? '');
    $html = (string) ($preview['html'] ?? '');
    $attachments = $preview['attachments'] ?? [];
@endphp

<div style="display: flex; flex-direction: column; gap: 0.75rem;">
    <div style="font-size: 0.82rem; color: #4b5563; line-height: 1.6;">
        <div><strong>Kam:</strong> {{ $to !== '' ? $to : '—' }}@if ($juristName !== '') ({{ $juristName }})@endif</div>
        <div><strong>Nosūtītājs:</strong> {{ $fromAddress }}@if ($fromName !== '') ({{ $fromName }})@endif</div>
        <div><strong>Temats:</strong> {{ $subject !== '' ? $subject : '—' }}</div>
        <div><strong>Pielikumi ({{ count($attachments) }}):</strong>
            @if ($attachments === [])
                nav
            @else
                <ul style="margin: 0.15rem 0 0 1.1rem; padding: 0;">
                    @foreach ($attachments as $attachment)
                        <li>{{ $attachment }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <iframe title="E-pasta priekšskatījums" srcdoc="{{ $html }}"
        style="width: 100%; height: 34rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #ffffff;"></iframe>
</div>
