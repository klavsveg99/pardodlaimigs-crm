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
                <p style="font-size: 0.8rem; color: #6b7280; margin: 0;">Nav uzdevumu, apskates, līdu, novecojušu īpašumu vai dzimšanas dienu.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                @foreach ($notifications as $item)
                    @php
                        if (! empty($item['task_id'])) {
                            $quickMethod = 'completeTask';
                        } elseif (! empty($item['viewing_id'])) {
                            $quickMethod = 'completeViewing';
                        } elseif (! empty($item['greet_client_id'])) {
                            $quickMethod = 'greetBirthday';
                        } else {
                            $quickMethod = null;
                        }
                        $quickId = $item['task_id'] ?? $item['viewing_id'] ?? $item['greet_client_id'] ?? null;
                        $quickTitle = ! empty($item['greet_client_id'])
                            ? 'Atzīmēt kā apsveiktu'
                            : (! empty($item['viewing_id']) ? 'Atzīmēt kā notikušu' : 'Atzīmēt kā pabeigtu');
                    @endphp
                    <div class="pdc-today-row" wire:key="today-{{ $item['key'] }}" style="display: flex; align-items: stretch; border: 1px solid {{ $item['urgent'] ? '#fca5a5' : '#e5e7eb' }}; border-left: 3px solid {{ $item['urgent'] ? '#dc2626' : 'var(--pdc-primary)' }}; border-radius: 0.7rem; background: #ffffff;">
                        <a
                            href="{{ $item['url'] }}"
                            style="display: flex; align-items: flex-start; gap: 0.85rem; padding: 0.75rem 0.9rem; flex: 1 1 auto; min-width: 0; text-decoration: none; color: inherit;"
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

                        </a>

                        <div style="flex: none; display: inline-flex; align-items: center; gap: 0.4rem; padding-right: 0.6rem;">
                            @if ($quickMethod)
                                <button
                                    type="button"
                                    x-data="{ busy: false }"
                                    x-on:click="if (busy) return; busy = true; $el.closest('.pdc-today-row').classList.add('is-completing'); $wire.{{ $quickMethod }}({{ $quickId }}).catch(() => { busy = false; $el.closest('.pdc-today-row').classList.remove('is-completing'); })"
                                    x-bind:disabled="busy"
                                    title="{{ $quickTitle }}"
                                    style="width: 1.7rem; height: 1.7rem; border-radius: 9999px; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;"
                                >
                                    <svg x-show="!busy" style="width: 0.95rem; height: 0.95rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <svg x-show="busy" x-cloak class="pdc-today-spinner" style="width: 0.95rem; height: 0.95rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 1 0 9 9"/></svg>
                                </button>
                            @endif
                            <x-filament::icon icon="heroicon-o-chevron-right" style="flex: none; width: 1.1rem; height: 1.1rem; color: #9ca3af;" />
                        </div>

                        @if (! empty($item['dismissable']))
                            <button
                                type="button"
                                wire:click="dismiss(@js($item['key']))"
                                title="Aizvērt"
                                style="flex: none; align-self: center; margin: 0 0.6rem 0 0; width: 1.7rem; height: 1.7rem; border-radius: 9999px; background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; line-height: 0;"
                            >
                                <svg style="width: 0.85rem; height: 0.85rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
