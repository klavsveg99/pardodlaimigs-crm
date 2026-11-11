@php
    $completedCount = collect($rows)->where('completed', true)->count();
    $date = fn ($value) => $value?->locale('lv')->translatedFormat('d.m.Y');
@endphp

<x-filament::section
    heading="Kam materiāls pieejams"
    :description="'Kopā: '.count($rows).' · Pabeiguši: '.$completedCount"
>
    @if ($rows === [])
        <p class="pdc-kb-audience-empty">Materiāls vēl nav pieejams nevienam lietotājam.</p>
    @else
        <div class="pdc-kb-audience">
            <style>
                .pdc-kb-audience { display: grid; gap: 0.45rem; }
                .pdc-kb-audience-empty { font-size: 0.82rem; color: #6b7280; margin: 0; }
                .pdc-kb-audience-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.6rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.55rem; }
                .pdc-kb-audience-person { display: grid; gap: 0.15rem; min-width: 0; }
                .pdc-kb-audience-person strong { font-size: 0.85rem; color: #111827; }
                .pdc-kb-audience-meta { font-size: 0.75rem; color: #6b7280; }
                .pdc-kb-audience-side { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
                .pdc-kb-audience-stars { color: #d97706; letter-spacing: 0.05em; white-space: nowrap; }
                html.dark .pdc-kb-audience-empty { color: #9ca3af; }
                html.dark .pdc-kb-audience-row { border-color: #27303a; }
                html.dark .pdc-kb-audience-person strong { color: #f3f4f6; }
                html.dark .pdc-kb-audience-meta { color: #9ca3af; }
            </style>

            @foreach ($rows as $row)
                <div class="pdc-kb-audience-row">
                    <div class="pdc-kb-audience-person">
                        <strong>{{ $row['name'] }}</strong>
                        <span class="pdc-kb-audience-meta">
                            {{ $row['role'] }}
                            @if ($row['completed_at'])
                                · Pabeigts {{ $date($row['completed_at']) }}
                            @elseif ($row['due_at'])
                                · Termiņš {{ $date($row['due_at']) }}
                            @endif
                        </span>
                    </div>
                    <div class="pdc-kb-audience-side">
                        @foreach ($row['sources'] as $source)
                            <x-filament::badge color="gray">{{ $source }}</x-filament::badge>
                        @endforeach
                        @if ($row['rating'])
                            <span class="pdc-kb-audience-stars">{{ str_repeat('★', $row['rating']).str_repeat('☆', 5 - $row['rating']) }}</span>
                        @endif
                        <x-filament::badge :color="$row['completed'] ? 'success' : 'warning'">
                            {{ $row['completed'] ? 'Pabeigts' : 'Jāizpilda' }}
                        </x-filament::badge>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament::section>
