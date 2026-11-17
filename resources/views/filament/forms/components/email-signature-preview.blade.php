@php
    $signature = $get('email_signature');
@endphp

<div>
    <div style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600;">
        E-pasta paraksts
    </div>

    <div class="pdc-signature-preview">
        @if (filled($signature))
            {!! $signature !!}
        @else
            <span class="pdc-signature-preview-empty">Paraksts vēl nav izveidots.</span>
        @endif
    </div>

    <div class="pdc-signature-preview-note">
        Tiek automātiski pievienots visiem e-pastiem, ko nosūtāt no CRM.
    </div>
</div>
