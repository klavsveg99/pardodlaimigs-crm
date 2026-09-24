<div>
@if (count($notices) > 0)
    @php
        $keyHash = md5(implode('|', array_column($notices, 'key')));
    @endphp

    <style>
        [x-cloak] { display: none !important; }
        @media (max-width: 640px) {
            .pdc-notice-stack { left: 0.5rem !important; right: 0.5rem !important; width: auto !important; }
        }
    </style>

    <div
        wire:key="notice-popup-{{ $keyHash }}"
        class="pdc-notice-stack"
        x-data="{ i: 0, total: {{ count($notices) }} }"
        style="position: fixed; top: 4.5rem; right: 0.9rem; z-index: 2147483000; width: 24rem; max-width: calc(100vw - 1rem); display: flex; flex-direction: column; gap: 0.5rem;"
    >
        @foreach ($notices as $idx => $notice)
            <div
                x-show="i === {{ $idx }}"
                x-cloak
                style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $notice['urgent'] ? '#dc2626' : 'var(--pdc-primary)' }}; border-radius: 0.75rem; background: #ffffff; box-shadow: 0 10px 30px rgba(0,0,0,0.14); padding: 0.8rem 0.9rem;"
            >
                <div style="display: flex; align-items: flex-start; gap: 0.6rem;">
                    <span style="flex: none; display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 0.55rem; background: {{ $notice['urgent'] ? '#fef2f2' : 'color-mix(in srgb, var(--pdc-primary) 10%, #ffffff)' }}; color: {{ $notice['urgent'] ? '#dc2626' : 'var(--pdc-primary)' }};">
                        <x-filament::icon :icon="$notice['icon']" style="width: 1.1rem; height: 1.1rem;" />
                    </span>

                    <div style="flex: 1 1 auto; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                            <x-filament::badge :color="$notice['type_color']">{{ $notice['type'] }}</x-filament::badge>
                            <a href="{{ $notice['url'] }}" style="font-size: 0.88rem; font-weight: 700; color: #111827; text-decoration: none; overflow-wrap: anywhere;">{{ $notice['title'] }}</a>
                        </div>

                        @if (! empty($notice['fields']))
                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem 0.9rem; margin-top: 0.35rem;">
                                @foreach ($notice['fields'] as $field)
                                    <span style="font-size: 0.77rem; color: #4b5563;">
                                        <span style="color: #9ca3af;">{{ $field['label'] }}:</span>
                                        <span style="font-weight: 600;">{{ $field['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-top: 0.55rem; min-height: 1.5rem;">
                            <a href="{{ $notice['url'] }}" style="font-size: 0.78rem; font-weight: 600; color: var(--pdc-primary); text-decoration: none;">Atvērt</a>

                            @if (count($notices) > 1)
                                <span style="display: inline-flex; align-items: center; gap: 0.3rem;">
                                    <button type="button" x-on:click="i = i > 0 ? i - 1 : total - 1" title="Iepriekšējais" style="width: 1.5rem; height: 1.5rem; border-radius: 9999px; border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; line-height: 1; padding: 0;">‹</button>
                                    <span style="font-size: 0.72rem; color: #6b7280;" x-text="(i + 1) + ' / ' + total"></span>
                                    <button type="button" x-on:click="i = i < total - 1 ? i + 1 : 0" title="Nākamais" style="width: 1.5rem; height: 1.5rem; border-radius: 9999px; border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; line-height: 1; padding: 0;">›</button>
                                </span>
                            @endif
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="dismiss(@js($notice['key']))"
                        title="Aizvērt"
                        style="flex: none; width: 1.7rem; height: 1.7rem; border-radius: 9999px; background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;"
                    >
                        <svg style="width: 0.85rem; height: 0.85rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
</div>
