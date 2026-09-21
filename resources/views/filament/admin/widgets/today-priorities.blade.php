<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Šodien jāizdara</x-slot>
        <x-slot name="description">{{ now()->locale('lv')->translatedFormat('l, d.m.Y') }}</x-slot>
        <x-slot name="afterHeader">
            <button
                type="button"
                wire:click="toggleOnlyMine"
                class="fi-btn fi-size-sm fi-outlined {{ $showOnlyMine ? 'fi-color-primary' : 'fi-color-gray' }}"
                style="display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap;"
            >
                <x-filament::icon icon="heroicon-o-user" style="width: 1rem; height: 1rem;" />
                <span>Mani uzdevumi</span>
            </button>
        </x-slot>

        @php
            $notifications = $this->getNotifications();
        @endphp

        @if (count($notifications) === 0)
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem; padding: 2rem 1rem; text-align: center;">
                <x-filament::icon icon="heroicon-o-check-circle" style="width: 2rem; height: 2rem; color: #16a34a;" />
                <p style="font-size: 0.9rem; font-weight: 600; color: #374151; margin: 0;">Šodien nekas nav jāveic</p>
                <p style="font-size: 0.8rem; color: #6b7280; margin: 0;">Nav uzdevumu, apskates vai novecojušu īpašumu.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                @foreach ($notifications as $item)
                    <a
                        href="{{ $item['url'] }}"
                        style="display: flex; align-items: flex-start; gap: 0.85rem; padding: 0.75rem 0.9rem; border: 1px solid {{ $item['urgent'] ? '#fca5a5' : '#e5e7eb' }}; border-left: 3px solid {{ $item['urgent'] ? '#dc2626' : 'var(--pdc-primary)' }}; border-radius: 0.7rem; background: #ffffff; text-decoration: none; color: inherit;"
                    >
                        <span style="flex: none; display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border-radius: 0.55rem; background: {{ $item['urgent'] ? '#fef2f2' : '#f3f4f6' }}; color: {{ $item['urgent'] ? '#dc2626' : '#374151' }};">
                            <x-filament::icon :icon="$item['icon']" style="width: 1.15rem; height: 1.15rem;" />
                        </span>

                        <div style="flex: 1 1 auto; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <x-filament::badge :color="$item['type_color']">{{ $item['type'] }}</x-filament::badge>
                                <span style="font-size: 0.9rem; font-weight: 700; color: #111827; overflow-wrap: anywhere;">{{ $item['title'] }}</span>
                                @if (! empty($item['status']))
                                    <x-filament::badge :color="$item['status_color'] ?? 'gray'">{{ $item['status'] }}</x-filament::badge>
                                @endif
                            </div>

                            @if (! empty($item['fields']))
                                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem 1.1rem; margin-top: 0.35rem;">
                                    @foreach ($item['fields'] as $field)
                                        <span style="font-size: 0.79rem; color: #4b5563;">
                                            <span style="color: #9ca3af;">{{ $field['label'] }}:</span>
                                            <span style="font-weight: 600;">{{ $field['value'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <x-filament::icon icon="heroicon-o-chevron-right" style="flex: none; width: 1.1rem; height: 1.1rem; color: #9ca3af; margin-top: 0.4rem;" />
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
