<x-filament-panels::page>
    <x-slot name="heading">
        <div class="flex items-center justify-between">
            <h1 class="fi-header-heading">{{ $record->title }}</h1>
            <div class="flex items-center gap-2">
                <x-filament::badge :color="$record->status_color ?? 'gray'" class="text-sm">
                    {{ $record->status_label ?? $record->status }}
                </x-filament::badge>
            </div>
        </div>
    </x-slot>

    <x-filament::section heading="Pamatdati">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategorija</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->category ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cena</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->price_display ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Atbildīgais</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->owner?->name ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">{{ $record->slug ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Īpašuma dati">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Istabas</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->beds ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vannas istabas</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->baths ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Platība (m²)</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->size_m2 ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Zemes platība (m²)</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->land_m2 ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5 md:col-span-2 lg:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kadastra nr.</dt>
                <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $record->kadastra_nr ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Atrašanās vieta">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pilsēta</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->city ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5 lg:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Adrese</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->address ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Koordinātes</dt>
                <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">
                    {{ $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : '—' }}
                </dd>
            </div>
        </div>
        @if($record->lat && $record->lng)
            <div class="mt-4 rounded-xl overflow-hidden border border-gray-200 dark:border-white/10" style="height: 220px;">
                <iframe width="100%" height="100%" frameborder="0" style="border:0" src="https://maps.google.com/maps?q={{ $record->lat }},{{ $record->lng }}&z=15&output=embed" allowfullscreen></iframe>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Apraksts">
        <div class="prose max-w-none text-sm text-gray-700 dark:text-gray-300">
            {!! $record->description ?: '<span class="text-gray-400">Nav apraksta.</span>' !!}
        </div>
    </x-filament::section>

    <x-filament::section heading="Pielikumi">
        @if ($record->attachments->isNotEmpty())
            <div class="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                @foreach ($record->attachments as $idx => $attachment)
                    <a href="{{ $attachment->url }}" target="_blank"
                       class="group relative block overflow-hidden rounded-xl border bg-gray-50 dark:bg-white/5 dark:border-white/10 {{ $idx === 0 ? 'border-[var(--pdc-primary)] ring-2 ring-[var(--pdc-primary)]/20' : 'border-gray-200' }}">
                        <img src="{{ $attachment->url }}"
                             alt="{{ $attachment->original_name }}"
                             class="h-32 w-full object-cover md:h-36">
                        @if($idx === 0)
                            <span class="absolute left-2 top-2 rounded bg-[var(--pdc-primary)] px-2 py-0.5 text-xs font-semibold text-white">GALVENĀ</span>
                        @endif
                        <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/60 via-transparent to-transparent p-2 opacity-0 transition group-hover:opacity-100">
                            <span class="truncate text-xs text-white">{{ $attachment->original_name }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center rounded-xl border-2 border-dashed border-gray-200 p-8 dark:border-white/10">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500">Pielikumu nav.</p>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Saistītie klienti">
        @if ($record->clients->isNotEmpty())
            <div class="flex flex-col gap-2">
                @foreach ($record->clients as $client)
                    <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--pdc-primary)] text-sm font-semibold text-white">
                                {{ strtoupper(substr($client->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $client->name }}</p>
                                <p class="text-sm text-gray-500">
                                    @php
                                        $relationClasses = [
                                            'seller' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                            'buyer' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                            'tenant' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            'landlord' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                        ];
                                        $cls = $relationClasses[$client->pivot->relation] ?? 'bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-300';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">
                                        {{ $client->pivot->relation_label ?: ucfirst($client->pivot->relation) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <a href="{{ \App\Filament\Admin\Resources\ClientResource::getUrl('view', ['record' => $client]) }}"
                           class="text-sm font-medium text-[var(--pdc-primary)] hover:underline">
                            Skatīt klientu
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Nav saistītu klientu.</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Datumi">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Izveidots</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->created_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Atjaunināts</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->updated_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Publicēts</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->published_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Derīgs līdz</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->expires_at?->format('d.m.Y') ?? '—' }}</dd>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
