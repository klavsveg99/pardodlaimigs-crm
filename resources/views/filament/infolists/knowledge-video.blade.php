<div style="display: grid; gap: 1rem;">
    @foreach ($videoUrls as $url)
        @php($embedUrl = $article?->getVideoEmbedUrl($url))
        <div style="display: grid; gap: 0.5rem;">
            @if ($embedUrl)
                <div style="position: relative; width: 100%; aspect-ratio: 16 / 9; overflow: hidden; border-radius: 0.5rem; background: #111;">
                    <iframe
                        src="{{ $embedUrl }}"
                        title="Video"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen
                        style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;"
                    ></iframe>
                </div>
            @endif
            <a href="{{ $url }}" target="_blank" rel="noopener">Atvērt video jaunā cilnē</a>
        </div>
    @endforeach
</div>
