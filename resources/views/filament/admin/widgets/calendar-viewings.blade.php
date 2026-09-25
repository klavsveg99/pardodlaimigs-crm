<x-filament-widgets::widget class="fi-wi-calendar-viewings">
    <x-filament::section heading="Kalendārs">
        @if (auth()->user()?->can('manage'))
            <div class="pdc-agent-filter-row">
                <label for="agent-filter-widget" class="pdc-agent-filter-label">Aģents:</label>
                <select
                    id="agent-filter-widget"
                    wire:model.live="agentFilter"
                    class="pdc-agent-filter"
                >
                    <option value="">Visi</option>
                    @foreach ($agentOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="fc-calendar-wrapper" wire:ignore>
            <div
                x-data="calendarCombined"
                data-calendar-viewings
                data-events="{{ $eventsJson }}"
            >
                <div x-ref="calendar"></div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
