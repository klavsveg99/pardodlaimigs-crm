@php
    $myAssignment = $assignment;
    $myCompletedAt = $myAssignment?->completed_at ?? $myCompletion?->completed_at;
@endphp

<section style="display: grid; gap: 0.6rem;">
    <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Statuss</h2>
    <p style="font-size: 0.9rem; margin: 0;">
        @if ($myAssignment && ! $myAssignment->completed_at)
            <x-filament::badge color="warning">Jāizpilda</x-filament::badge>
            @if ($myAssignment->due_at)
                <span style="color: #6b7280; margin-left: 0.4rem;">Termiņš {{ $myAssignment->due_at->locale('lv')->translatedFormat('d.m.Y') }}</span>
            @endif
        @elseif ($myCompletedAt)
            <x-filament::badge color="success">Pabeigts</x-filament::badge>
            <span style="color: #6b7280; margin-left: 0.4rem;">{{ $myCompletedAt->locale('lv')->translatedFormat('d.m.Y H:i') }}</span>
        @else
            <x-filament::badge color="warning">Jāizpilda</x-filament::badge>
        @endif

        @if ($isPublic)
            <x-filament::badge color="gray" style="margin-left: 0.4rem;">Obligāts saturs</x-filament::badge>
        @endif
    </p>
</section>
