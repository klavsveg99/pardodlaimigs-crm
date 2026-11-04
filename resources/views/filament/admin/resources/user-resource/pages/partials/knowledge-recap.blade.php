<x-filament::section
    heading="Zināšanu bāze"
    description="Kursi un materiāli, kas obligāti vai piešķirti lietotājam"
>
    <style>
        .pdc-kb-recap-summary { display: grid; gap: 0.4rem; font-size: 0.85rem; color: #6b7280; }
        .pdc-kb-recap-summary strong { color: #111827; }
        html.dark .pdc-kb-recap-summary { color: #9ca3af; }
        html.dark .pdc-kb-recap-summary strong { color: #f3f4f6; }
    </style>

    <div class="pdc-kb-recap-summary">
        <div>
            Piešķirti kursi: <strong>{{ count($knowledge['assigned']) }}</strong>
            · Pabeigti kursi: <strong>{{ $knowledge['completed'] }}</strong>
        </div>
        <div>
            Materiāli: <strong>{{ count($knowledge['materials']) }}</strong>
            · Pabeigti materiāli: <strong>{{ $knowledge['materials_completed'] }}</strong>
        </div>
    </div>
</x-filament::section>
