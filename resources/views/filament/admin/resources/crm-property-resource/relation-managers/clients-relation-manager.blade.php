<div class="fi-resource-relation-manager">
    {{ $this->content }}

    <x-filament-panels::unsaved-action-changes-alert />

    {{-- Shared "Paldies par sadarbību" popup — atveras ar
         `pdc-open-client-email` notikumu no rindas "Nosūtīt" pogas. --}}
    @include('filament.partials.property-client-email-popup')
</div>
