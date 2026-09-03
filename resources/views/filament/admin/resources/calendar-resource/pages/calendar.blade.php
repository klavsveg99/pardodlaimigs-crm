<x-filament-panels::page>
    <div class="flex items-center justify-end mb-4">
        <label for="agent-filter" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">Aģents:</label>
        <select
            id="agent-filter"
            wire:model.live="agentFilter"
            class="pdc-agent-filter"
        >
            <option value="">Visi</option>
            @foreach ($agentOptions as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="fc-calendar-wrapper">
        <div
            x-data="calendarCombined"
            data-calendar-viewings
            data-events="{{ $eventsJson }}"
        >
            <div x-ref="calendar"></div>
        </div>
    </div>
</x-filament-panels::page>