@php
    /** @var \App\Models\PropertySearch|null $search */
    $search = $getRecord();
@endphp

@if ($search instanceof \App\Models\PropertySearch)
    <x-filament::section heading="Meklēšanas progress">
        @if ($search->isProgressActive())
            <div class="pdc-search-progress-panel">
                <div class="pdc-search-progress-head">
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                    <span>{{ $search->progress_status === \App\Models\PropertySearch::PROGRESS_QUEUED ? 'Gaida rindā' : 'Notiek meklēšana' }}</span>
                </div>
                <div class="pdc-search-progress-message">{{ $search->progress_message ?: 'Sagatavo meklēšanu...' }}</div>
                <div class="pdc-search-progress-track" aria-hidden="true">
                    <span class="pdc-search-progress-bar"></span>
                </div>
                <div class="pdc-search-progress-meta">
                    <span>Sākts: {{ $search->progress_started_at?->format('d.m.Y H:i') ?? 'vēl nav sākts' }}</span>
                    <span>Atrasti: {{ $search->progress_found }} jauni objekti</span>
                </div>
            </div>
        @elseif ($search->progress_status === \App\Models\PropertySearch::PROGRESS_COMPLETED)
            <div class="pdc-search-progress-done">
                <span>
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <strong>Pabeigts</strong>
                    {{ $search->progress_finished_at?->format('d.m.Y H:i') }}
                </span>
                <span>{{ $search->progress_message }}</span>
            </div>
        @elseif ($search->progress_status === \App\Models\PropertySearch::PROGRESS_FAILED)
            <div class="pdc-search-progress-failed">
                <span>
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <strong>Neizdevās</strong>
                    {{ $search->progress_finished_at?->format('d.m.Y H:i') }}
                </span>
                <span>{{ $search->progress_message }}</span>
            </div>
        @else
            <div class="pdc-search-progress-message">Meklēšana vēl nav palaista.</div>
        @endif
    </x-filament::section>
@endif
