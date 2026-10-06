<x-filament-panels::page>
    {{ $this->content }}

    {{-- Shared "Paldies par sadarbību" popup — tabulas ātrā darbība
         "Nosūtīt paldies" to atver ar `pdc-open-client-email` notikumu. --}}
    @include('filament.partials.property-client-email-popup')
</x-filament-panels::page>
