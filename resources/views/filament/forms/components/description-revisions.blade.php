@php
    $record = $getRecord();
    $revisions = $record?->descriptionRevisions()->with('user')->limit(30)->orderByDesc('id')->get() ?? collect();
@endphp

@if ($revisions->isEmpty())
    <div style="text-align: center; padding: 1.5rem; border: 1px dashed #d1d5db; border-radius: 0.75rem;">
        <p style="font-size: 0.875rem; color: #6b7280;">Nav saglabātu apraksta versiju.</p>
        <p style="margin-top: 0.25rem; font-size: 0.75rem; color: #9ca3af;">Versija tiek saglabāta katru reizi, kad apraksts tiek mainīts un saglabāts ar "Saglabāt". Pēdējās 30 versijas ir pieejamas.</p>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @foreach ($revisions as $revision)
            <div
                wire:key="desc-rev-{{ $revision->id }}"
                style="border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 0.75rem; overflow: hidden;"
            >
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; padding: 0.55rem 1rem; border-bottom: 1px solid #e5e7eb; background: #ffffff;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 0.875rem;">
                        <span style="font-weight: 600; color: #111827;">{{ $revision->created_at?->format('d.m.Y H:i') }}</span>
                        @if ($loop->first)
                            <span style="background: var(--pdc-primary); color: white; font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 0.4rem; letter-spacing: 0.04em; line-height: 1;">JAUNĀKĀ</span>
                        @endif
                        <span style="color: #6b7280;">{{ $revision->user?->name ?? '—' }}</span>
                    </div>
                    <button
                        type="button"
                        wire:click="revertDescription({{ $revision->id }})"
                        class="fi-btn fi-size-sm fi-color fi-color-gray fi-outlined"
                        title="Ielādēt šo versiju redaktorā"
                    >
                        <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 016 6v3"/></svg>
                        <span>Atjaunot</span>
                    </button>
                </div>
                <div class="prose" style="max-height: 12rem; overflow-y: auto; padding: 0.75rem 1rem; font-size: 0.875rem; color: #374151; line-height: 1.6;">
                    {!! $revision->description ?: '<span style="color: #9ca3af;">(Tukšs apraksts)</span>' !!}
                </div>
            </div>
        @endforeach
    </div>
@endif
