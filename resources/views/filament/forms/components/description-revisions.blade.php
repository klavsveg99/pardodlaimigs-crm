@php
    $record = $getRecord();
    $revisions = $record?->descriptionRevisions()->with('user')->limit(30)->orderByDesc('id')->get() ?? collect();
@endphp

<style>
    .dark .pdc-rev-card { background: #111827 !important; border-color: #27303a !important; }
    .dark .pdc-rev-head { background: #1f2937 !important; border-color: #27303a !important; }
    .dark .pdc-rev-time { color: #f3f4f6 !important; }
    .dark .pdc-rev-user { color: #9ca3af !important; }
    .dark .pdc-rev-body { color: #d1d5db !important; }
</style>

@if ($revisions->isEmpty())
    <div class="pdc-empty-state">
        <div class="pdc-empty-state-inner">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
            <span>Nav saglabātu apraksta versiju.</span>
        </div>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @foreach ($revisions as $revision)
            <div
                wire:key="desc-rev-{{ $revision->id }}"
                class="pdc-rev-card"
                style="border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 0.75rem; overflow: hidden;"
            >
                <div class="pdc-rev-head" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; padding: 0.55rem 1rem; border-bottom: 1px solid #e5e7eb; background: #ffffff;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 0.875rem;">
                        <span class="pdc-rev-time" style="font-weight: 600; color: #111827;">{{ $revision->created_at?->format('d.m.Y H:i') }}</span>
                        @if ($loop->first)
                            <span style="background: var(--pdc-primary); color: white; font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 0.4rem; letter-spacing: 0.04em; line-height: 1;">JAUNĀKĀ</span>
                        @endif
                        <span class="pdc-rev-user" style="color: #6b7280;">{{ $revision->user?->name ?? '-' }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                        <button
                            type="button"
                            wire:click="revertDescription({{ $revision->id }})"
                            class="fi-btn fi-size-sm fi-color fi-color-gray fi-outlined"
                            title="Ielādēt šo versiju redaktorā"
                        >
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 016 6v3"/></svg>
                            <span>Atjaunot</span>
                        </button>
                        <button
                            type="button"
                            wire:click="confirmDescriptionRevisionDeletion({{ $revision->id }})"
                            class="fi-btn fi-size-sm fi-color fi-color-danger fi-outlined"
                            title="Dzēst šo versiju neatgriezeniski"
                        >
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            <span>Dzēst</span>
                        </button>
                    </div>
                </div>
                <div class="prose pdc-rev-body" style="max-height: 12rem; overflow-y: auto; padding: 0.75rem 1rem; font-size: 0.875rem; color: #374151; line-height: 1.6;">
                    {!! $revision->description ?: '<span style="color: #9ca3af;">(Tukšs apraksts)</span>' !!}
                </div>
            </div>
        @endforeach
    </div>
@endif
