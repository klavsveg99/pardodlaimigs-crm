<x-filament-panels::page>
    <x-filament::section>
        <dl class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Iesniegts</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Klients</dt>
                <dd class="mt-1 text-sm">
                    @if ($record->client)
                        <a href="{{ \App\Filament\Admin\Resources\ClientResource::getUrl('view', ['record' => $record->client]) }}"
                           class="font-semibold text-[var(--pdc-primary)] dark:text-white underline decoration-[var(--pdc-primary)]/30 hover:no-underline">
                            {{ $record->client->name }}
                        </a>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">Nav piesaistīts</span>
                    @endif
                </dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Forma</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->form_name }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Statuss</dt>
                <dd class="mt-1">
                    <x-filament::badge :color="\App\Filament\Admin\Resources\WpformEntryResource::STATUS_COLORS[$record->status] ?? 'gray'">
                        {{ \App\Filament\Admin\Resources\WpformEntryResource::STATUSES[$record->status] ?? $record->status ?? '—' }}
                    </x-filament::badge>
                </dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section heading="Iesniegtā informācija">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
        @forelse ($record->fields ?? [] as $field)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $field['name'] ?? '' }}
                </dt>
                <dd class="mt-2 text-sm text-gray-900 dark:text-white break-words whitespace-pre-wrap">
                    @php $value = $field['value'] ?? ''; @endphp
                    @if (is_array($value))
                        {{ implode(', ', array_map(fn ($v) => (string) $v, $value)) }}
                    @elseif ($value === '' || $value === null)
                        <span class="text-gray-400 dark:text-gray-500">—</span>
                    @else
                        {!! nl2br(trim(e($value))) !!}
                    @endif
                </dd>
            </div>
        @empty
            <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">Nav datu.</p>
        @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>