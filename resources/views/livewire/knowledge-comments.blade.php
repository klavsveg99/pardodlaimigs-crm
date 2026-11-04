@php
    $date = fn ($value) => $value?->locale('lv')->translatedFormat('d.m.Y H:i');
@endphp

<section class="pdc-kb-comments">
    <style>
        .pdc-kb-comments { display: grid; gap: 0.75rem; }
        .pdc-kb-comments-title { font-size: 0.9rem; font-weight: 700; color: #111827; margin: 0; }
        .pdc-kb-comment { padding: 0.65rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.6rem; background: #ffffff; }
        .pdc-kb-comment-head { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; }
        .pdc-kb-comment-author { font-size: 0.82rem; font-weight: 700; color: #111827; }
        .pdc-kb-comment-date { font-size: 0.72rem; color: #9ca3af; white-space: nowrap; }
        .pdc-kb-comment-body { margin-top: 0.25rem; font-size: 0.85rem; line-height: 1.45; color: #374151; overflow-wrap: anywhere; }
        .pdc-kb-comments-empty { font-size: 0.82rem; color: #6b7280; margin: 0; }
        .pdc-kb-comment-form { display: grid; gap: 0.5rem; }
        .pdc-kb-comment-form textarea {
            width: 100%;
            min-height: 4.5rem;
            resize: vertical;
            padding: 0.55rem 0.7rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background: #ffffff;
            font-size: 0.85rem;
            line-height: 1.45;
            color: #111827;
        }
        .pdc-kb-comment-form textarea:focus { outline: 2px solid var(--pdc-primary, #285854); outline-offset: 1px; border-color: transparent; }
        .pdc-kb-comment-form-footer { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
        .pdc-kb-comment-error { font-size: 0.78rem; color: #dc2626; }
        html.dark .pdc-kb-comments-title { color: #f3f4f6; }
        html.dark .pdc-kb-comment { border-color: #27303a; background: #0f151b; }
        html.dark .pdc-kb-comment-author { color: #f3f4f6; }
        html.dark .pdc-kb-comment-date { color: #6b7280; }
        html.dark .pdc-kb-comment-body { color: #d1d5db; }
        html.dark .pdc-kb-comments-empty { color: #9ca3af; }
        html.dark .pdc-kb-comment-form textarea { background: #0f151b; border-color: #374151; color: #f3f4f6; }
    </style>

    @if ($comments->isNotEmpty())
        <div style="display: grid; gap: 0.5rem;">
            @foreach ($comments as $comment)
                <article class="pdc-kb-comment" wire:key="kb-comment-{{ $comment->id }}">
                    <div class="pdc-kb-comment-head">
                        <span class="pdc-kb-comment-author">{{ $comment->user?->name ?? 'Dzēsts lietotājs' }}</span>
                        <span class="pdc-kb-comment-date">{{ $date($comment->created_at) }}</span>
                    </div>
                    <div class="pdc-kb-comment-body">{!! nl2br(e($comment->body)) !!}</div>
                </article>
            @endforeach
        </div>
    @else
        <p class="pdc-kb-comments-empty">Komentāru vēl nav.</p>
    @endif

    <form class="pdc-kb-comment-form" wire:submit="addComment">
        <textarea wire:model="body" maxlength="2000" placeholder="Uzrakstīt komentāru" aria-label="Komentārs"></textarea>
        @error('body') <span class="pdc-kb-comment-error">{{ $message }}</span> @enderror
        <div class="pdc-kb-comment-form-footer">
            <span class="pdc-kb-comment-date">{{ mb_strlen($body) }}/2000</span>
            <x-filament::button type="submit" size="sm" color="primary" wire:loading.attr="disabled">
                Pievienot komentāru
            </x-filament::button>
        </div>
    </form>
</section>
