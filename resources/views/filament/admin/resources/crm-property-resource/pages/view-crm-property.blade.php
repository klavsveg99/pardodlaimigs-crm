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
            @php
                $galleryJson = $record->attachments->map(fn($a)=>["url"=>$a->url,"name"=>$a->original_name])->values()->toJson();
                $galleryUid = "view-gallery-".$record->id;
            @endphp
            <script type="application/json" id="{{ $galleryUid }}-data">{!! $galleryJson !!}</script>
            <div
                x-data="{
                    open: false,
                    index: 0,
                    items: [],
                    init(){ try{ this.items = JSON.parse(document.getElementById('{{ $galleryUid }}-data').textContent) || []; }catch(e){ this.items=[]; } },
                    get current() { return this.items[this.index] || null; },
                    show(i){ this.index=i; this.open=true; document.body.style.overflow='hidden'; },
                    close(){ this.open=false; document.body.style.overflow=''; },
                    prev(){ this.index = this.index>0 ? this.index-1 : this.items.length-1; },
                    next(){ this.index = this.index < this.items.length-1 ? this.index+1 : 0; },
                }"
                x-on:keydown.escape.window="if(open) close()"
                x-on:keydown.arrow-left.window="if(open) prev()"
                x-on:keydown.arrow-right.window="if(open) next()"
            >
                <div class="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                    @foreach ($record->attachments as $idx => $attachment)
                        <button
                            type="button"
                            x-on:click="show({{ $idx }})"
                            class="group relative block overflow-hidden rounded-xl border bg-gray-50 dark:bg-white/5 text-left transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 cursor-zoom-in {{ $idx === 0 ? 'border-[var(--pdc-primary)] ring-2 ring-[var(--pdc-primary)]/20' : 'border-gray-200 dark:border-white/10' }}"
                            title="Atvērt galerijā • {{ $attachment->original_name }}"
                        >
                            <img src="{{ $attachment->url }}"
                                 alt="{{ $attachment->original_name }}"
                                 class="h-32 w-full object-cover md:h-36 pointer-events-none">
                            @if($idx === 0)
                                <span style="position:absolute; left:0.5rem; top:0.5rem; background:var(--pdc-primary); color:white; font-size:0.68rem; font-weight:700; padding:0.28rem 0.55rem; border-radius:0.4rem; letter-spacing:0.04em; box-shadow:0 2px 8px rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.2); line-height:1;">GALVENĀ</span>
                            @endif
                            <div class="absolute inset-0 flex items-end p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200" style="background: linear-gradient(to top, rgba(0,0,0,0.55) 0%, transparent 45%);">
                                <span style="background:var(--pdc-primary); color:white; font-size:0.72rem; font-weight:600; padding:0.22rem 0.5rem; border-radius:0.35rem; box-shadow:0 1px 4px rgba(0,0,0,0.25); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; max-width:100%;">{{ $attachment->original_name }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>

                <!-- Lightbox Gallery -->
                <template x-if="open">
                    <div
                        x-transition.opacity
                        style="position:fixed; inset:0; z-index:99999; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.92); padding:1rem;"
                        x-on:click.self="close()"
                    >
                    <button type="button" x-on:click="close()" style="position:absolute; top:1rem; right:1rem; z-index:10; width:2.5rem; height:2.5rem; border-radius:9999px; background:rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter:blur(4px);">
                        <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <button type="button" x-show="items.length>1" x-on:click.stop="prev()" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); z-index:10; width:2.75rem; height:2.75rem; border-radius:9999px; background:rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter:blur(4px);">
                        <svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" x-show="items.length>1" x-on:click.stop="next()" style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); z-index:10; width:2.75rem; height:2.75rem; border-radius:9999px; background:rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter:blur(4px);">
                        <svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div style="max-width:90vw; max-height:90vh; display:flex; flex-direction:column; align-items:center; gap:0.75rem;">
                        <img :src="current?.url" :alt="current?.name" style="max-width:90vw; max-height:78vh; object-fit:contain; border-radius:0.5rem; box-shadow:0 8px 32px rgba(0,0,0,0.5);"/>
                        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; justify-content:center;">
                            <span style="background:var(--pdc-primary); color:white; font-size:0.78rem; font-weight:600; padding:0.3rem 0.7rem; border-radius:9999px;" x-text="(index+1)+' / '+items.length"></span>
                            <span style="background:var(--pdc-primary); color:white; font-size:0.78rem; font-weight:600; padding:0.3rem 0.7rem; border-radius:0.5rem; max-width:60vw; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="current?.name"></span>
                            <span x-show="index===0" style="background:var(--pdc-primary); color:white; font-size:0.7rem; font-weight:700; padding:0.3rem 0.6rem; border-radius:0.4rem; letter-spacing:0.04em;">GALVENĀ</span>
                        </div>
                    </div>
                    </div>
                </template>
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
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
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
        </div>
    </x-filament::section>
</x-filament-panels::page>
