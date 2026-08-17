<x-filament-panels::page>
    <x-filament::section>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nosaukums</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->title }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategorija</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->category ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Statuss</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->status_label }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cena</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->price_display ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Atrašanās vieta">
        <div class="grid gap-3 sm:grid-cols-2">
            <div><span class="text-sm text-gray-500 dark:text-gray-400">Pilsēta:</span> {{ $record->city ?: '—' }}</div>
            <div><span class="text-sm text-gray-500 dark:text-gray-400">Adrese:</span> {{ $record->address ?: '—' }}</div>
            <div><span class="text-sm text-gray-500 dark:text-gray-400">Kadastra nr.:</span> {{ $record->kadastra_nr ?: '—' }}</div>
            <div><span class="text-sm text-gray-500 dark:text-gray-400">Koordinātes:</span> {{ $record->lat && $record->lng ? "$record->lat, $record->lng" : '—' }}</div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Apraksts">
        <div class="prose max-w-none text-sm text-gray-700 dark:prose-invert dark:text-gray-300">
            {!! $record->description ?: '<span class="text-gray-400">Nav apraksta.</span>' !!}
        </div>
    </x-filament::section>

    <x-filament::section heading="Pielikumi">
        @if ($record->attachments->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($record->attachments as $attachment)
                    <a href="{{ $attachment->url }}" target="_blank" class="rounded-xl border border-gray-200 p-3 text-sm text-primary-600 hover:bg-gray-50 dark:border-white/10 dark:text-primary-400 dark:hover:bg-white/5">
                        {{ $attachment->original_name }}
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Pielikumu nav.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
