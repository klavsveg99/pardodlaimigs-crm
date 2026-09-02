<x-filament-panels::page>
    <x-filament::section>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-[#27303a] dark:bg-[#0b0f14]">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nosaukums</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->title ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-[#27303a] dark:bg-[#0b0f14]">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Klients</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    @if ($record->client)
                        <a
                            href="{{ \App\Filament\Admin\Resources\ClientResource::getUrl('view', ['record' => $record->client_id]) }}"
                            class="font-semibold text-[var(--pdc-primary-darker)] dark:text-[var(--pdc-primary)] underline decoration-[var(--pdc-primary)]/30 underline-offset-2 hover:text-[var(--pdc-primary)] dark:hover:text-[var(--pdc-primary-darker)]"
                        >
                            {{ $record->client->name }}
                        </a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-[#27303a] dark:bg-[#0b0f14]">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vērtība</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->value_eur ? number_format((float) $record->value_eur, 2, ',', ' ') . ' €' : '—' }}</dd>
            </div>
            <div class="rounded-xl border border-[var(--pdc-primary)]/30 bg-[var(--pdc-primary)]/10 p-4 dark:border-[var(--pdc-primary)]/30 dark:bg-[var(--pdc-primary)]/10">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pašreizējais posms</dt>
                <dd class="mt-1 text-sm font-bold text-[var(--pdc-primary-darker)] dark:text-[var(--pdc-primary)]">{{ $record->stage_label }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Posmu vēsture">
        <div class="relative space-y-3 ps-3">
            @forelse ($record->stageChanges as $change)
                <div class="relative rounded-xl border border-gray-200 bg-gray-50 p-4 ps-5 dark:border-[#27303a] dark:bg-[#0b0f14]">
                    <span class="absolute -start-[0.45rem] top-5 h-3 w-3 rounded-full bg-[var(--pdc-primary)] ring-4 ring-white dark:ring-gray-900"></span>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        @if ($change->payload['initial'] ?? false)
                            Sākotnējais posms:
                        @else
                            {{ \App\Models\Deal::STAGES[$change->payload['from'] ?? ''] ?? ($change->payload['from'] ?? '—') }}
                            <span class="px-1 text-gray-400">→</span>
                        @endif
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
