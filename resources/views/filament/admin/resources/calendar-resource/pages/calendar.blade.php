<x-filament-panels::page>
    <div class="flex items-center justify-end mb-4">
        <label for="agent-filter" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-200">Aģents:</label>
        <select
            id="agent-filter"
            wire:model.live="agentFilter"
            class="fi-input block w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-[var(--pdc-primary)] focus:ring-2 focus:ring-[var(--pdc-primary)]/20 dark:border-white/10 dark:bg-white/5 dark:text-white"
        >
            <option value="">Visi</option>
            @foreach ($agentOptions as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="fc-calendar-wrapper" style="height: 760px;">
        <div
            x-data="calendarCombined"
            data-calendar-viewings
            data-events="{{ $eventsJson }}"
            data-height="700"
        >
            <div x-ref="calendar"></div>
        </div>
    </div>
</x-filament-panels::page>