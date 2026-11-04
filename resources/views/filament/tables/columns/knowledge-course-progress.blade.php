@php
    $stats = $getRecord()['stats'] ?? ['total' => 0, 'completed' => 0, 'percent' => 0];
@endphp

<div style="display: flex; align-items: center; gap: 0.5rem; min-width: 9rem;">
    <div style="flex: 1 1 auto; height: 0.35rem; min-width: 4rem; border-radius: 9999px; background: #e5e7eb; overflow: hidden;">
        <span style="display: block; height: 100%; border-radius: inherit; background: var(--pdc-primary, #285854); width: {{ $stats['percent'] }}%;"></span>
    </div>
    <span style="font-size: 0.72rem; color: #6b7280; white-space: nowrap;">
        {{ $stats['completed'] }}/{{ $stats['total'] }} · {{ $stats['percent'] }}%
    </span>
</div>
