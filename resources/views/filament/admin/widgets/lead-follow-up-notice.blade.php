<x-filament-widgets::widget>
@php
    $leads = $this->getLeads();
    $isAdmin = auth()->user()?->can('manage') ?? false;
@endphp

@if (count($leads) > 0)
    <div style="border: 1px solid var(--pdc-primary); border-left: 4px solid var(--pdc-primary); background: color-mix(in srgb, var(--pdc-primary) 7%, #ffffff); border-radius: 0.75rem; padding: 0.85rem 1rem;">
        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
            <x-filament::icon icon="heroicon-o-phone-arrow-up-right" style="flex: none; width: 1.35rem; height: 1.35rem; color: var(--pdc-primary); margin-top: 0.1rem;" />

            <div style="flex: 1 1 auto; min-width: 0;">
                <p style="margin: 0; font-size: 0.9rem; font-weight: 700; color: var(--pdc-primary);">
                    Līdi — jāsazinās ({{ count($leads) }})
                </p>
                <p style="margin: 0.15rem 0 0; font-size: 0.78rem; color: #4b5563;">
                    Sazinies ar klientu un atjaunini informāciju, lai sadarbību varētu uzsākt.
                </p>

                <div style="display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.6rem;">
                    @foreach ($leads as $lead)
                        <div style="display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.35rem 0.9rem; font-size: 0.82rem; color: #374151;">
                            <a href="{{ $lead['url'] }}" style="font-weight: 700; color: var(--pdc-primary); text-decoration: none; overflow-wrap: anywhere;">{{ $lead['name'] }}</a>
                            @if ($lead['phone'])
                                <a href="tel:{{ preg_replace('/\s+/', '', $lead['phone']) }}" style="color: #374151; text-decoration: none;">{{ $lead['phone'] }}</a>
                            @endif
                            @if ($lead['email'])
                                <a href="mailto:{{ $lead['email'] }}" style="color: #374151; text-decoration: none; overflow-wrap: anywhere;">{{ $lead['email'] }}</a>
                            @endif
                            @if ($lead['source'])
                                <span><span style="color: #6b7280;">Avots:</span> {{ $lead['source'] }}</span>
                            @endif
                            @if ($lead['created'])
                                <span><span style="color: #6b7280;">Pievienots:</span> {{ $lead['created'] }}</span>
                            @endif
                            @if ($isAdmin && $lead['agent'])
                                <span><span style="color: #6b7280;">Aģents:</span> {{ $lead['agent'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <button
                type="button"
                wire:click="dismiss"
                title="Aizvērt"
                style="flex: none; width: 1.9rem; height: 1.9rem; border-radius: 9999px; background: color-mix(in srgb, var(--pdc-primary) 10%, #ffffff); color: var(--pdc-primary); border: 1px solid color-mix(in srgb, var(--pdc-primary) 30%, #ffffff); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;"
            >
                <svg style="width: 0.95rem; height: 0.95rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
@endif
</x-filament-widgets::widget>
