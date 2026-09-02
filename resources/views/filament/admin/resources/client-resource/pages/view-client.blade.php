<x-filament-panels::page>
    <x-slot name="heading">
        <div class="flex items-center justify-between">
            <h1 class="fi-header-heading">{{ $record->name }}</h1>
        </div>
    </x-slot>

    <x-filament::section heading="Klienta dati">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-pasts</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->email ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tālrunis</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->phone ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Personas kods</dt>
                <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $record->personas_kods ?: '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Avots</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->source ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Piezīmes">
        <div class="prose max-w-none text-sm text-gray-700 dark:text-gray-300">
            {!! $record->notes_md ?: '<span class="text-gray-400">Nav piezīmju.</span>' !!}
        </div>
    </x-filament::section>

    <x-filament::section heading="Pielikumi">
        @if ($record->attachments->isNotEmpty())
            <div class="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                @foreach ($record->attachments as $attachment)
                    <a href="{{ $attachment->url }}" target="_blank" class="block rounded-xl border border-gray-200 bg-gray-50 p-2 dark:border-white/10 dark:bg-white/5">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $attachment->original_name }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Nav pielikumu.</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Saistītie dati">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Darījumi</dt>
                <dd class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $record->deals_count ?? $record->deals?->count() ?? 0 }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Apskates</dt>
                <dd class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $record->viewings_count ?? $record->viewings?->count() ?? 0 }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Īpašumi</dt>
                <dd class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $record->crm_properties_count ?? $record->crmProperties?->count() ?? 0 }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Datumi">
        <div class="grid gap-3 grid-cols-1 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Izveidots</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Atjaunināts</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->updated_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">GDPR piekrišana</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->gdpr_consent_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>