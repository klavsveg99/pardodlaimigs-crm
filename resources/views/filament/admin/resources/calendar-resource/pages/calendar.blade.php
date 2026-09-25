<x-filament-panels::page>
    @if (auth()->user()?->can('manage'))
        <div class="pdc-agent-filter-row">
            <label for="agent-filter" class="pdc-agent-filter-label">Aģents:</label>
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
    @endif

    {{-- wire:ignore: Livewire nedrīkst morfot FullCalendar ģenerēto DOM,
         citādi aģenta filtra maiņa sagrauj kalendāra izkārtojumu. --}}
    <div class="fc-calendar-wrapper" wire:ignore>
        <div
            x-data="calendarCombined"
            data-calendar-viewings
            data-events="{{ $eventsJson }}"
        >
            <div x-ref="calendar"></div>
        </div>
    </div>
</x-filament-panels::page>
