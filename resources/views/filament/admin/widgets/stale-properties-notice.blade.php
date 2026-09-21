<x-filament-widgets::widget>
@php
    $properties = $this->getProperties();
@endphp

@if (count($properties) > 0)
    <div style="border: 1px solid #fcd34d; border-left: 4px solid #f59e0b; background: #fffbeb; border-radius: 0.75rem; padding: 0.85rem 1rem;">
        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" style="flex: none; width: 1.35rem; height: 1.35rem; color: #d97706; margin-top: 0.1rem;" />

            <div style="flex: 1 1 auto; min-width: 0;">
                <p style="margin: 0; font-size: 0.9rem; font-weight: 700; color: #92400e;">
                    Cena un statuss nav mainīti {{ \App\Models\CrmProperty::STALE_AFTER_DAYS }} dienas ({{ count($properties) }})
                </p>
                <div style="display: flex; flex-direction: column; gap: 0.3rem; margin-top: 0.5rem;">
                    @foreach ($properties as $property)
                        <a href="{{ $property['url'] }}" style="display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.35rem 0.9rem; font-size: 0.82rem; color: #78350f; text-decoration: none;">
                            <span style="font-weight: 700; color: #92400e; overflow-wrap: anywhere;">{{ $property['title'] }}</span>
                            <span><span style="color: #b45309;">Bez izmaiņām:</span> <strong>{{ $property['days'] }} d.</strong></span>
                            <span><span style="color: #b45309;">Pārdošanas sākums:</span> <strong>{{ $property['sale_started_at'] }}</strong></span>
                            <span><span style="color: #b45309;">Līguma periods:</span> <strong>{{ $property['partnership'] }}</strong></span>
                        </a>
                    @endforeach
                </div>
            </div>

            <button
                type="button"
                wire:click="dismiss"
                title="Aizvērt"
                style="flex: none; width: 1.9rem; height: 1.9rem; border-radius: 9999px; background: rgba(146, 64, 14, 0.08); color: #92400e; border: 1px solid rgba(146, 64, 14, 0.25); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;"
            >
                <svg style="width: 0.95rem; height: 0.95rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
@endif
</x-filament-widgets::widget>
