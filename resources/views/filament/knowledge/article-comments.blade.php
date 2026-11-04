@php
    $steps = is_array($article->steps) ? $article->steps : [];
@endphp

<x-filament::section
    heading="Komentāri"
    description="Jautājumi un piezīmes par šo materiālu"
>
    @livewire(
        \App\Livewire\KnowledgeComments::class,
        ['articleId' => $article->id],
        key('kb-article-comments-'.$article->id)
    )
</x-filament::section>

@if ($steps !== [])
    <x-filament::section
        heading="Komentāri pa soļiem"
        description="Komentē konkrētu kursa soli"
    >
        <div class="pdc-kb-step-comments">
            <style>
                .pdc-kb-step-comments { display: grid; gap: 1rem; }
                .pdc-kb-step-comment { display: grid; gap: 0.5rem; padding-top: 0.85rem; border-top: 1px solid #e5e7eb; }
                .pdc-kb-step-comment:first-child { padding-top: 0; border-top: 0; }
                .pdc-kb-step-comment-title { font-size: 0.85rem; font-weight: 700; color: #111827; }
                html.dark .pdc-kb-step-comment { border-color: #27303a; }
                html.dark .pdc-kb-step-comment-title { color: #f3f4f6; }
            </style>

            @foreach ($steps as $index => $step)
                <div class="pdc-kb-step-comment">
                    <div class="pdc-kb-step-comment-title">
                        {{ $index + 1 }}. {{ $step['title'] ?? 'Solis' }}
                    </div>
                    @livewire(
                        \App\Livewire\KnowledgeComments::class,
                        ['articleId' => $article->id, 'stepIndex' => $index],
                        key('kb-step-comments-'.$article->id.'-'.$index)
                    )
                </div>
            @endforeach
        </div>
    </x-filament::section>
@endif
