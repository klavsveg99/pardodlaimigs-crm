<section style="display: grid; gap: 0.75rem;">
    <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Faili</h2>
    @foreach ($attachments as $attachment)
        <div style="display: grid; gap: 0.4rem;">
            @if (str_starts_with((string) $attachment->mime_type, 'video/'))
                <video controls preload="metadata" style="width: 100%; max-height: 28rem; border-radius: 0.5rem; background: #111;">
                    <source src="{{ $attachment->cacheBustedUrl() }}" type="{{ $attachment->mime_type }}">
                </video>
            @elseif ($attachment->isImage())
                <a href="{{ $attachment->cacheBustedUrl() }}" target="_blank" rel="noopener">
                    <img src="{{ $attachment->thumbUrl() }}" alt="{{ $attachment->original_name }}" loading="lazy" style="max-width: 100%; max-height: 24rem; object-fit: contain; border-radius: 0.4rem;">
                </a>
            @endif
            <a href="{{ $attachment->cacheBustedUrl() }}" target="_blank" rel="noopener" style="font-weight: 600;">
                {{ $attachment->original_name }}
            </a>
        </div>
    @endforeach
</section>
