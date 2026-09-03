<x-filament-panels::page>
    <div class="flex items-center justify-end mb-4">
        <label for="agent-filter" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">Aģents:</label>
        <select
            id="agent-filter"
            wire:model.live="agentFilter"
            class="fi-select block w-48 cursor-pointer appearance-none rounded-lg border border-gray-300 bg-white bg-no-repeat py-2 pl-3 pr-9 text-sm text-gray-900 shadow-sm transition focus:border-[var(--pdc-primary)] focus:ring-2 focus:ring-[var(--pdc-primary)]/20 focus:outline-none dark:border-white/10 dark:bg-[#18181b] dark:text-gray-200 dark:hover:border-white/20 dark:focus:border-[var(--pdc-primary)] [&>option]:bg-white dark:[&>option]:bg-[#18181b] dark:[&>option]:text-gray-200"
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