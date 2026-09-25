<div>
@if (count($notices) > 0)
    @php
        $keyHash = md5(implode('|', array_column($notices, 'key')));
    @endphp

    {{-- Tailwind utility classes are NOT compiled for CRM blades, so this
         popup is styled with explicit pdc-notice-* rules (incl. dark mode)
         instead of utility classes. --}}
    <style>
        [x-cloak] { display: none !important; }

        .pdc-notice-stack {
            position: fixed;
            top: 4.5rem;
            right: 1rem;
            z-index: 2147483000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 24rem;
            max-width: calc(100vw - 1rem);
        }
        @media (max-width: 640px) {
            .pdc-notice-stack { left: 0.5rem; right: 0.5rem; width: auto; }
        }

        .pdc-notice-card {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-left: 4px solid var(--pdc-primary);
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        .pdc-notice-card--urgent { border-left-color: #dc2626; }

        .pdc-notice-row { display: flex; align-items: flex-start; gap: 0.625rem; }

        .pdc-notice-icon {
            display: inline-flex;
            flex: none;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            background: #f3f4f6;
            color: #374151;
        }
        .pdc-notice-icon--urgent { background: #fef2f2; color: #dc2626; }
        .pdc-notice-icon .fi-icon { width: 1rem; height: 1rem; }

        .pdc-notice-body { flex: 1 1 auto; min-width: 0; }
        .pdc-notice-head { display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem; }
        .pdc-notice-title { font-size: 0.875rem; font-weight: 700; color: #111827; text-decoration: none; }
        .pdc-notice-fields { display: flex; flex-wrap: wrap; margin-top: 0.375rem; column-gap: 0.875rem; row-gap: 0.25rem; }
        .pdc-notice-field { font-size: 0.75rem; color: #4b5563; }
        .pdc-notice-field-label { color: #9ca3af; }
        .pdc-notice-field-value { font-weight: 600; }

        .pdc-notice-foot { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; min-height: 1.5rem; margin-top: 0.5rem; }
        .pdc-notice-open { font-size: 0.75rem; font-weight: 600; color: var(--pdc-primary); text-decoration: none; }
        .pdc-notice-nav { display: inline-flex; align-items: center; gap: 0.375rem; }
        .pdc-notice-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 9999px;
            background: #ffffff;
            color: #374151;
            line-height: 1;
            cursor: pointer;
        }
        .pdc-notice-counter { font-size: 0.75rem; color: #6b7280; }

        .pdc-notice-close {
            display: inline-flex;
            flex: none;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 9999px;
            background: #f3f4f6;
            color: #4b5563;
            cursor: pointer;
        }
        .pdc-notice-close svg { width: 0.875rem; height: 0.875rem; }

        /* Dark mode */
        .dark .pdc-notice-card { border-color: #374151; background: #111827; }
        .dark .pdc-notice-icon { background: #1f2937; color: #e5e7eb; }
        .dark .pdc-notice-icon--urgent { background: #450a0a; color: #fca5a5; }
        .dark .pdc-notice-title { color: #f3f4f6; }
        .dark .pdc-notice-field { color: #9ca3af; }
        .dark .pdc-notice-field-label { color: #6b7280; }
        .dark .pdc-notice-nav-btn { border-color: #374151; background: #1f2937; color: #e5e7eb; }
        .dark .pdc-notice-counter { color: #9ca3af; }
        .dark .pdc-notice-close { border-color: #374151; background: #1f2937; color: #d1d5db; }
    </style>

    <div
        wire:key="notice-popup-{{ $keyHash }}"
        class="pdc-notice-stack"
        x-data="{ i: 0, total: {{ count($notices) }} }"
    >
        @foreach ($notices as $idx => $notice)
            <div
                x-show="i === {{ $idx }}"
                x-cloak
                class="pdc-notice-card {{ $notice['urgent'] ? 'pdc-notice-card--urgent' : '' }}"
            >
                <div class="pdc-notice-row">
                    <span class="pdc-notice-icon {{ $notice['urgent'] ? 'pdc-notice-icon--urgent' : '' }}">
                        <x-filament::icon :icon="$notice['icon']" />
                    </span>

                    <div class="pdc-notice-body">
                        <div class="pdc-notice-head">
                            <x-filament::badge :color="$notice['type_color']">{{ $notice['type'] }}</x-filament::badge>
                            <a href="{{ $notice['url'] }}" class="pdc-notice-title">{{ $notice['title'] }}</a>
                        </div>

                        @if (! empty($notice['fields']))
                            <div class="pdc-notice-fields">
                                @foreach ($notice['fields'] as $field)
                                    <span class="pdc-notice-field">
                                        <span class="pdc-notice-field-label">{{ $field['label'] }}:</span>
                                        <span class="pdc-notice-field-value">{{ $field['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="pdc-notice-foot">
                            <a href="{{ $notice['url'] }}" class="pdc-notice-open">Atvērt</a>

                            @if (count($notices) > 1)
                                <span class="pdc-notice-nav">
                                    <button type="button" x-on:click="i = i > 0 ? i - 1 : total - 1" title="Iepriekšējais" class="pdc-notice-nav-btn">‹</button>
                                    <span class="pdc-notice-counter" x-text="(i + 1) + ' / ' + total"></span>
                                    <button type="button" x-on:click="i = i < total - 1 ? i + 1 : 0" title="Nākamais" class="pdc-notice-nav-btn">›</button>
                                </span>
                            @endif
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="dismiss(@js($notice['key']))"
                        title="Aizvērt"
                        class="pdc-notice-close"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
</div>
