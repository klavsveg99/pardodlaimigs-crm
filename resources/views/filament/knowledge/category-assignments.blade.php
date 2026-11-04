@php
    $progress = app(\App\Services\KnowledgeProgress::class);
    $date = fn ($value) => $value?->locale('lv')->translatedFormat('d.m.Y');
@endphp

@if ($category)
<section class="pdc-kb-category-assignments">
    <style>
        .pdc-kb-category-assignments { display: grid; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #e5e7eb; }
        .pdc-kb-category-title { font-size: 0.9rem; font-weight: 700; color: #111827; margin: 0; }
        .pdc-kb-category-empty { font-size: 0.82rem; color: #6b7280; margin: 0; }
        .pdc-kb-category-list { display: grid; gap: 0.45rem; }
        .pdc-kb-category-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.6rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.55rem; }
        .pdc-kb-category-person { display: grid; gap: 0.15rem; min-width: 0; }
        .pdc-kb-category-person strong { font-size: 0.85rem; color: #111827; }
        .pdc-kb-category-meta { font-size: 0.75rem; color: #6b7280; }
        .pdc-kb-category-side { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .pdc-kb-category-progress { font-size: 0.75rem; color: #4b5563; white-space: nowrap; }
        .pdc-kb-category-stars { color: #d97706; letter-spacing: 0.05em; white-space: nowrap; }
        .pdc-kb-category-comments { display: grid; gap: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6; }
        html.dark .pdc-kb-category-assignments { border-color: #27303a; }
        html.dark .pdc-kb-category-title { color: #f3f4f6; }
        html.dark .pdc-kb-category-empty { color: #9ca3af; }
        html.dark .pdc-kb-category-row { border-color: #27303a; }
        html.dark .pdc-kb-category-person strong { color: #f3f4f6; }
        html.dark .pdc-kb-category-meta, html.dark .pdc-kb-category-progress { color: #9ca3af; }
        html.dark .pdc-kb-category-comments { border-color: #1f2831; }
    </style>

    <h2 class="pdc-kb-category-title">Piešķīrumi</h2>

    @if ($assignments->isEmpty())
        <p class="pdc-kb-category-empty">Kurss vēl nav piešķirts nevienam aģentam.</p>
    @else
        <div class="pdc-kb-category-list">
            @foreach ($assignments as $assignment)
                @php
                    $stats = $assignment->assignedTo
                        ? $progress->courseProgress($assignment->assignedTo, $category)
                        : ['total' => 0, 'completed' => 0, 'percent' => 0];
                @endphp
                <div class="pdc-kb-category-row">
                    <div class="pdc-kb-category-person">
                        <strong>{{ $assignment->assignedTo?->name ?? 'Dzēsts lietotājs' }}</strong>
                        <span class="pdc-kb-category-meta">
                            @if ($assignment->completed_at)
                                Pabeigts {{ $date($assignment->completed_at) }}
                            @elseif ($assignment->due_at)
                                Termiņš {{ $date($assignment->due_at) }}
                            @else
                                Bez termiņa
                            @endif
                            @if ($assignment->assignedBy)
                                · Piešķīra {{ $assignment->assignedBy->name }}
                            @endif
                        </span>
                    </div>
                    <div class="pdc-kb-category-side">
                        <span class="pdc-kb-category-progress">{{ $stats['completed'] }}/{{ $stats['total'] }} · {{ $stats['percent'] }}%</span>
                        @if ($assignment->rating)
                            <span class="pdc-kb-category-stars">{{ str_repeat('★', $assignment->rating).str_repeat('☆', 5 - $assignment->rating) }}</span>
                        @endif
                        <x-filament::badge :color="$assignment->completed_at ? 'success' : 'gray'">
                            {{ $assignment->completed_at ? 'Pabeigts' : 'Nav pabeigts' }}
                        </x-filament::badge>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="pdc-kb-category-comments">
        <h3 class="pdc-kb-category-title">Komentāri par kursu</h3>
        @livewire(
            \App\Livewire\KnowledgeComments::class,
            ['categoryId' => $category->id],
            key('kb-admin-category-comments-'.$category->id)
        )
    </div>
</section>
@endif
