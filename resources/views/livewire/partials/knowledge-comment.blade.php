@php
    $comment = $node['comment'];
    $isReplying = $replyTo === $comment->id;
@endphp

<article class="pdc-kb-comment {{ $depth > 0 ? 'pdc-kb-comment--reply' : '' }}" wire:key="kb-comment-{{ $comment->id }}">
    <div class="pdc-kb-comment-head">
        <span class="pdc-kb-comment-author">{{ $comment->user?->name ?? 'Dzēsts lietotājs' }}</span>
        <span class="pdc-kb-comment-date">{{ $comment->created_at?->locale('lv')->translatedFormat('d.m.Y H:i') }}</span>
    </div>
    <div class="pdc-kb-comment-body">{!! nl2br(e($comment->body)) !!}</div>

    <div class="pdc-kb-comment-actions">
        <button type="button" class="pdc-kb-reply-btn" wire:click="startReply({{ $comment->id }})">
            Atbildēt
        </button>
    </div>

    @if ($isReplying)
        <form class="pdc-kb-comment-form pdc-kb-reply-form" wire:submit="addReply">
            <textarea wire:model="replyBody" maxlength="2000" placeholder="Uzrakstīt atbildi" aria-label="Atbilde"></textarea>
            @error('replyBody') <span class="pdc-kb-comment-error">{{ $message }}</span> @enderror
            <div class="pdc-kb-comment-form-footer">
                <button type="button" class="pdc-kb-cancel-btn" wire:click="cancelReply">Atcelt</button>
                <x-filament::button type="submit" size="sm" color="primary" wire:loading.attr="disabled">
                    Nosūtīt atbildi
                </x-filament::button>
            </div>
        </form>
    @endif

    @if ($node['replies'] !== [])
        <div class="pdc-kb-replies">
            @foreach ($node['replies'] as $reply)
                @include('livewire.partials.knowledge-comment', ['node' => $reply, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</article>
