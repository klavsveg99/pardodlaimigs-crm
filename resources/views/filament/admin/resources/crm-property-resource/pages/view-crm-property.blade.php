<x-filament-panels::page>
    <x-slot name="heading">
        <div class="flex items-center justify-between">
            <h1 class="fi-header-heading">{{ $record->title }}</h1>
            <div class="flex items-center gap-2">
                <x-filament::badge :color="$record->status_color" class="text-sm">
                    {{ $record->status_label }}
                </x-filament::badge>
            </div>
        </div>
    </x-slot>

    <x-filament::section heading="Pamatdati">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategorija</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->category ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cena</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->price_display ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Atbildīgais</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->owner?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $record->slug ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Īpašuma dati">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Istabas</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->beds ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vannas istabas</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->baths ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Platība (m²)</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->size_m2 ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Zemes platība (m²)</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->land_m2 ?: '—' }}</dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kadastra nr.</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $record->kadastra_nr ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Atrašanās vieta">
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pilsēta</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->city ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Adrese</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->address ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Koordinātes</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                    {{ $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : '—' }}
                </dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Apraksts">
        <div class="prose max-w-none text-sm text-gray-700 dark:prose-invert dark:text-gray-300">
            {!! $record->description ?: '<span class="text-gray-400 dark:text-gray-500">Nav apraksta.</span>' !!}
        </div>
    </x-filament::section>

    <x-filament::section heading="Pielikumi">
        @if ($record->attachments->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($record->attachments as $attachment)
                    <a href="{{ $attachment->url }}" target="_blank"
                       class="group relative rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden hover:border-[var(--pdc-primary)] dark:hover:border-[var(--pdc-primary)] transition-colors aspect-video bg-gray-50 dark:bg-white/5">
                        <img src="{{ $attachment->url }}"
                             alt="{{ $attachment->original_name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                            <span class="text-white text-xs truncate block w-full">{{ $attachment->original_name }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-white/10 rounded-xl">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Pielikumu nav.</p>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Saistītie klienti">
        @if ($record->clients->isNotEmpty())
            <div class="space-y-2">
                @foreach ($record->clients as $client)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                        <div class="flex items-center gap-3">
                            <x-filament::avatar :label="$client->name" class="h-10 w-10 bg-[var(--pdc-primary)]" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $client->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    @php
                                        $relationClasses = [
                                            'seller' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                            'buyer' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                            'tenant' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            'landlord' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                        ];
                                        $class = $relationClasses[$client->pivot->relation] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $class }}">
                                        {{ $client->pivot->relation_label ?: ucfirst($client->pivot->relation) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <a href="{{ ClientResource::getUrl('view', ['record' => $client]) }}"
                           class="text-sm text-[var(--pdc-primary-darker)] dark:text-[var(--pdc-primary)] hover:underline">
                            Skatīt klientu
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nav saistītu klientu.</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Datumi">
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Izveidots</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->created_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Atjaunināts</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->updated_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Publicēts</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->published_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aizvairs</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->expires_at?->format('d.m.Y') ?? '—' }}</dd>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
