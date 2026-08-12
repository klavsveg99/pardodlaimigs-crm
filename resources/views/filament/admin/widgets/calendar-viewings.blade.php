<x-filament-widgets::widget class="fi-wi-calendar-viewings">
    <x-filament::section heading="Kalendārs">
        <div class="fc-calendar-wrapper" style="height: 660px;">
            <div
                x-data="calendarCombined"
                data-calendar-viewings
                data-events="{{ $eventsJson }}"
                data-height="600"
            >
                <div x-ref="calendar"></div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
