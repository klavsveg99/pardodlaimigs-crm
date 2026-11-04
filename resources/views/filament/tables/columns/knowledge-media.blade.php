@php
    $record = $getRecord();
    $hasVideo = filled($record->video_urls)
        || collect($record->steps ?? [])->contains(fn ($step): bool => filled($step['video_url'] ?? null));
    $hasAttachments = ($record->knowledge_attachments_count ?? 0) > 0;
@endphp

@if ($hasVideo || $hasAttachments)
    <span style="display: inline-flex; align-items: center; gap: 0.4rem; color: #9ca3af; font-size: 0.85rem;">
        @if ($hasVideo)
            <i class="fa-solid fa-video" title="Video" aria-label="Video"></i>
        @endif
        @if ($hasAttachments)
            <i class="fa-solid fa-file-lines" title="Pielikumi" aria-label="Pielikumi"></i>
        @endif
    </span>
@endif
