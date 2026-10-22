@php
    // Same row layout as the client/property attachment lists.
    $fileSize = static function (?int $bytes): string {
        if (! $bytes) {
            return '';
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return max(1, (int) round($bytes / 1024)).' KB';
        }

        return rtrim(rtrim(number_format($bytes / 1048576, 1, '.', ''), '0'), '.').' MB';
    };
@endphp

<style>
    .pdc-kb-file {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.65rem;
        padding: 0.5rem 0.75rem;
        background: #ffffff;
    }
    .pdc-kb-file-thumb {
        flex: none;
        height: 3rem;
        width: 3rem;
        border-radius: 0.55rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        text-decoration: none;
    }
    .pdc-kb-file-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .pdc-kb-file-body {
        flex: 1 1 auto;
        min-width: 0;
    }
    .pdc-kb-file-name {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-decoration: none;
    }
    .pdc-kb-file-name:hover {
        text-decoration: underline;
    }
    .pdc-kb-file-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.15rem;
        font-size: 0.75rem;
        color: #6b7280;
    }
    .pdc-kb-file-download {
        flex: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 2rem;
        width: 2rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #4b5563;
        text-decoration: none;
    }
    .dark .pdc-kb-file { background: #111827; border-color: #27303a; }
    .dark .pdc-kb-file-thumb { background: #0b0f14; border-color: #27303a; color: #9ca3af; }
    .dark .pdc-kb-file-name { color: #f3f4f6; }
    .dark .pdc-kb-file-meta { color: #9ca3af; }
    .dark .pdc-kb-file-download { background: #0b0f14; border-color: #27303a; color: #9ca3af; }
</style>

<section style="display: flex; flex-direction: column; gap: 0.5rem;">
    <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Faili</h2>
    @foreach ($attachments as $attachment)
        <div class="pdc-kb-file">
            <a class="pdc-kb-file-thumb" href="{{ $attachment->cacheBustedUrl() }}" target="_blank" rel="noopener" title="Atvērt">
                @if ($attachment->isImage())
                    <img src="{{ $attachment->thumbUrl() }}" alt="{{ $attachment->original_name }}" loading="lazy">
                @elseif (str_starts_with((string) $attachment->mime_type, 'video/'))
                    <i class="fa-solid fa-circle-play" style="font-size: 1.35rem;"></i>
                @else
                    <i class="fa-solid fa-file-lines" style="font-size: 1.35rem;"></i>
                @endif
            </a>
            <div class="pdc-kb-file-body">
                <a class="pdc-kb-file-name" href="{{ $attachment->cacheBustedUrl() }}" target="_blank" rel="noopener">{{ $attachment->original_name }}</a>
                <div class="pdc-kb-file-meta">
                    @if ($attachment->created_at)
                        <span>{{ $attachment->created_at->format('d.m.Y') }}</span>
                    @endif
                    @if ($attachment->created_at && $fileSize($attachment->size))
                        <span>·</span>
                    @endif
                    @if ($fileSize($attachment->size))
                        <span>{{ $fileSize($attachment->size) }}</span>
                    @endif
                </div>
            </div>
            <a class="pdc-kb-file-download" href="{{ $attachment->cacheBustedUrl() }}" download="{{ $attachment->original_name }}" title="Lejupielādēt">
                <i class="fa-solid fa-download" style="font-size: 0.85rem;"></i>
            </a>
        </div>
    @endforeach
</section>
