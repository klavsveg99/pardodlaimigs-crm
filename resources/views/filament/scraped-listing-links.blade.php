<ul style="display: grid; gap: 0.5rem; margin: 0; padding: 0; list-style: none;">
    @foreach ($records as $record)
        <li>
            <a href="{{ $record->url }}" target="_blank" rel="noopener" class="fi-link fi-size-sm">
                {{ $record->title }}
            </a>
        </li>
    @endforeach
</ul>
