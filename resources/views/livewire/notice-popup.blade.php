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
        class="pdc-notice-stack fixed right-4 top-[4.5rem] z-[2147483000] flex w-96 max-w-[calc(100vw-1rem)] flex-col gap-2"
        x-data="{ i: 0, total: {{ count($notices) }} }"
    >
        @foreach ($notices as $idx => $notice)
            <div
                x-show="i === {{ $idx }}"
                x-cloak
                class="rounded-xl border border-gray-200 bg-white p-3 shadow-xl dark:border-gray-700 dark:bg-gray-900"
                style="border-left: 4px solid {{ $notice['urgent'] ? '#dc2626' : 'var(--pdc-primary)' }};"
            >
                <div class="flex items-start gap-2.5">
                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-md {{ $notice['urgent'] ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                        <x-filament::icon :icon="$notice['icon']" class="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-filament::badge :color="$notice['type_color']">{{ $notice['type'] }}</x-filament::badge>
                            <a href="{{ $notice['url'] }}" class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $notice['title'] }}</a>
                        </div>

                        @if (! empty($notice['fields']))
                            <div class="mt-1.5 flex flex-wrap gap-x-3.5 gap-y-1">
                                @foreach ($notice['fields'] as $field)
                                    <span class="text-xs text-gray-600 dark:text-gray-300">
                                        <span class="text-gray-400 dark:text-gray-500">{{ $field['label'] }}:</span>
                                        <span class="font-semibold">{{ $field['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-2 flex min-h-6 items-center justify-between gap-2">
                            <a href="{{ $notice['url'] }}" class="text-xs font-semibold text-[color:var(--pdc-primary)]">Atvērt</a>

                            @if (count($notices) > 1)
                                <span class="inline-flex items-center gap-1.5">
                                    <button type="button" x-on:click="i = i > 0 ? i - 1 : total - 1" title="Iepriekšējais" class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 leading-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">‹</button>
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="(i + 1) + ' / ' + total"></span>
                                    <button type="button" x-on:click="i = i < total - 1 ? i + 1 : 0" title="Nākamais" class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 leading-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">›</button>
                                </span>
                            @endif
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="dismiss(@js($notice['key']))"
                        title="Aizvērt"
                        class="flex h-7 w-7 flex-none items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
</div>
