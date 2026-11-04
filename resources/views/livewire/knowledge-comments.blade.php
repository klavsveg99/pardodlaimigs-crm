@php
    $date = fn ($value) => $value?->locale('lv')->translatedFormat('d.m.Y H:i');
@endphp

<section class="pdc-kb-comments">
    <style>
        .pdc-kb-comments { display: grid; gap: 0.75rem; }
        .pdc-kb-comment { padding: 0.65rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.6rem; background: #ffffff; }
        .pdc-kb-comment--reply { background: #f9fafb; }
        .pdc-kb-comment-head { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; }
        .pdc-kb-comment-author { font-size: 0.82rem; font-weight: 700; color: #111827; }
        .pdc-kb-comment-date { font-size: 0.72rem; color: #9ca3af; white-space: nowrap; }
        .pdc-kb-comment-body { margin-top: 0.25rem; font-size: 0.85rem; line-height: 1.45; color: #374151; overflow-wrap: anywhere; }
        .pdc-kb-comment-actions { margin-top: 0.4rem; }
        .pdc-kb-reply-btn, .pdc-kb-cancel-btn { padding: 0; border: 0; background: transparent; font-size: 0.75rem; font-weight: 600; color: var(--pdc-primary, #285854); cursor: pointer; }
        .pdc-kb-reply-btn:hover, .pdc-kb-cancel-btn:hover { text-decoration: underline; }
        .pdc-kb-cancel-btn { color: #6b7280; }
        .pdc-kb-replies { display: grid; gap: 0.5rem; margin-top: 0.6rem; padding-left: 0.9rem; border-left: 2px solid #e5e7eb; }
        .pdc-kb-comments-empty { font-size: 0.82rem; color: #6b7280; margin: 0; }
        .pdc-kb-comment-form { display: grid; gap: 0.5rem; }
        .pdc-kb-reply-form { margin-top: 0.55rem; padding-top: 0.55rem; border-top: 1px solid #f3f4f6; }
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
        .pdc-kb-comments-head { font-size: 0.8rem; font-weight: 700; color: #374151; margin: 0; }
        html.dark .pdc-kb-comment { border-color: #27303a; background: #0f151b; }
        html.dark .pdc-kb-comment--reply { background: #111820; }
        html.dark .pdc-kb-comment-author { color: #f3f4f6; }
        html.dark .pdc-kb-comment-date { color: #6b7280; }
        html.dark .pdc-kb-comment-body { color: #d1d5db; }
        html.dark .pdc-kb-replies { border-color: #27303a; }
        html.dark .pdc-kb-reply-form { border-color: #1f2831; }
        html.dark .pdc-kb-comments-empty { color: #9ca3af; }
        html.dark .pdc-kb-comment-form textarea { background: #0f151b; border-color: #374151; color: #f3f4f6; }
        html.dark .pdc-kb-comments-head { color: #e5e7eb; }
        @media (max-width: 639px) {
            .pdc-kb-replies { padding-left: 0.6rem; }
            .pdc-kb-comment { padding: 0.55rem 0.6rem; }
        }
    </style>

    @if ($threads !== [])
        <div style="display: grid; gap: 0.5rem;">
            @foreach ($threads as $thread)
                @include('livewire.partials.knowledge-comment', ['node' => $thread, 'depth' => 0])
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
