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
        <div style="display: grid; gap: 1rem; grid-template-columns: repeat(4, 1fr);">
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Kategorija</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->category ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Cena</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; font-weight: 600; color: #111827;">{{ $record->price_display ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Atbildīgais</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->owner?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Slug</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827; font-family: monospace;">{{ $record->slug ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Īpašuma dati">
        <div style="display: grid; gap: 1rem; grid-template-columns: repeat(4, 1fr);">
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Istabas</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->beds ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Vannas istabas</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->baths ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Platība (m²)</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->size_m2 ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Zemes platība (m²)</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->land_m2 ?: '—' }}</dd>
            </div>
            <div style="grid-column: span 2;">
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Kadastra nr.</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827; font-family: monospace;">{{ $record->kadastra_nr ?: '—' }}</dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Atrašanās vieta">
        <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(3, 1fr);">
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Pilsēta</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->city ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Adrese</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->address ?: '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Koordinātes</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827; font-family: monospace;">
                    {{ $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : '—' }}
                </dd>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Apraksts">
        <div style="font-size: 0.875rem; color: #374151;">
            {!! $record->description ?: '<span style="color: #9ca3af;">Nav apraksta.</span>' !!}
        </div>
    </x-filament::section>

    <x-filament::section heading="Pielikumi">
        @if ($record->attachments->isNotEmpty())
            <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(5, 1fr);">
                @foreach ($record->attachments as $attachment)
                    <a href="{{ $attachment->url }}" target="_blank"
                       style="position: relative; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e5e7eb; display: block; aspect-ratio: 16/9; background: #f9fafb; transition: border-color 0.2s;">
                        <img src="{{ $attachment->url }}"
                             alt="{{ $attachment->original_name }}"
                             style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent, transparent); opacity: 0; display: flex; align-items: flex-end; padding: 0.75rem; transition: opacity 0.2s;"
                             onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                            <span style="color: white; font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; width: 100%;">{{ $attachment->original_name }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 2rem; border: 2px dashed #e5e7eb; border-radius: 0.75rem;">
                <svg style="margin: 0 auto; height: 3rem; width: 3rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;">Pielikumu nav.</p>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Saistītie klienti">
        @if ($record->clients->isNotEmpty())
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                @foreach ($record->clients as $client)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; background: #f9fafb;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: #285854; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 500;">
                                {{ strtoupper(substr($client->name, 0, 1)) }}
                            </div>
                            <div>
                                <p style="font-weight: 500; color: #111827;">{{ $client->name }}</p>
                                <p style="font-size: 0.875rem; color: #6b7280;">
                                    @php
                                        $relationClasses = [
                                            'seller' => 'background: #fee2e2; color: #991b1b;',
                                            'buyer' => 'background: #dcfce7; color: #166534;',
                                            'tenant' => 'background: #fef9c3; color: #854d0e;',
                                            'landlord' => 'background: #dbeafe; color: #1e40af;',
                                        ];
                                        $style = $relationClasses[$client->pivot->relation] ?? 'background: #f3f4f6; color: #374151;';
                                    @endphp
                                    <span style="{{ $style }} padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        {{ $client->pivot->relation_label ?: ucfirst($client->pivot->relation) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <a href="{{ ClientResource::getUrl('view', ['record' => $client]) }}"
                           style="font-size: 0.875rem; color: #285854; text-decoration: none;">
                            Skatīt klientu
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p style="font-size: 0.875rem; color: #6b7280;">Nav saistītu klientu.</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Datumi">
        <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(2, 1fr);">
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Izveidots</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->created_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Atjaunināts</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->updated_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Publicēts</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->published_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Aizvairs</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $record->expires_at?->format('d.m.Y') ?? '—' }}</dd>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
