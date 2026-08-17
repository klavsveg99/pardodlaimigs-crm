<x-filament-panels::page>
    <x-filament::section>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nosaukums</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->title ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Klients</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->client?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vērtība</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->value_eur ? number_format((float) $record->value_eur, 2, ',', ' ') . ' €' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pašreizējais posms</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->stage_label }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Posmu vēsture">
        <div class="space-y-4">
            @forelse ($record->stageChanges as $change)
                <div class="border-s-2 border-primary-500 ps-4">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ \App\Models\Deal::STAGES[$change->payload['from'] ?? ''] ?? ($change->payload['from'] ?? '—') }}
                        <span class="px-1 text-gray-400">→</span>
                        {{ \App\Models\Deal::STAGES[$change->payload['to'] ?? ''] ?? ($change->payload['to'] ?? '—') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $change->created_at?->format('d.m.Y H:i:s') ?? '—' }}
                        @if ($change->actor)
                            · {{ $change->actor->name }}
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Šim darījumam vēl nav reģistrētu posma maiņu.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
