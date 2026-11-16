@php
    /** @var \App\Models\PropertySearch $search */
    $search = $getRecord();
    $status = $search->progress_status;
    $active = $search->isProgressActive();
@endphp

@if ($active)
    <div class="pdc-search-progress">
        <div class="pdc-search-progress-head">
            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
            <span>{{ $status === \App\Models\PropertySearch::PROGRESS_QUEUED ? 'Gaida rindā' : 'Notiek meklēšana' }}</span>
        </div>
        <div class="pdc-search-progress-message">{{ $search->progress_message ?: 'Sagatavo meklēšanu...' }}</div>
        <div class="pdc-search-progress-track" aria-hidden="true">
            <span class="pdc-search-progress-bar"></span>
        </div>
    </div>
@elseif ($status === \App\Models\PropertySearch::PROGRESS_COMPLETED)
    <div class="pdc-search-progress-done">
        <span><strong>Pabeigts</strong> {{ $search->progress_finished_at?->format('d.m.Y H:i') }}</span>
        <span>{{ $search->progress_found }} jauni objekti</span>
    </div>
@elseif ($status === \App\Models\PropertySearch::PROGRESS_FAILED)
    <div class="pdc-search-progress-failed" title="{{ $search->progress_message }}">
        <span><strong>Neizdevās</strong> {{ $search->progress_finished_at?->format('d.m.Y H:i') }}</span>
        <span>{{ \Illuminate\Support\Str::limit((string) $search->progress_message, 70) }}</span>
    </div>
@else
    <span style="color: #9ca3af;">-</span>
@endif
