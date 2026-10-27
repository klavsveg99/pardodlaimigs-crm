@php
    $visibleAssignments = $isAdmin
        ? $assignments
        : $assignments->where('assigned_user_id', auth()->id());
    $myAssignment = $visibleAssignments->first(fn ($assignment) => $assignment->completed_at === null)
        ?? $visibleAssignments->first();
    $myCompletedAt = $myAssignment?->completed_at ?? $myCompletion?->completed_at;
@endphp

<section style="display: grid; gap: 0.6rem;">
    @if ($isAdmin)
        <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Piešķīrumi</h2>
        @if ($visibleAssignments->isEmpty())
            <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">Materiāls vēl nav piešķirts nevienam lietotājam.</p>
        @endif
        @foreach ($visibleAssignments as $assignment)
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.65rem 0.8rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                <div style="display: grid; gap: 0.15rem;">
                    <strong>{{ $assignment->assignedTo?->name ?? 'Dzēsts lietotājs' }}</strong>
                    <span style="font-size: 0.85rem; color: #6b7280;">
                        @if ($assignment->completed_at)
                            Pabeigts {{ $assignment->completed_at->locale('lv')->translatedFormat('d.m.Y H:i') }}
                        @elseif ($assignment->due_at)
                            Termiņš {{ $assignment->due_at->locale('lv')->translatedFormat('d.m.Y') }}
                        @else
                            Gaida izpildi
                        @endif
                    </span>
                </div>
                <x-filament::badge :color="$assignment->completed_at ? 'success' : 'gray'">
                    {{ $assignment->completed_at ? 'Pabeigts' : 'Nav pabeigts' }}
                </x-filament::badge>
            </div>
        @endforeach

        @if ($completions->isNotEmpty())
            <h2 style="font-size: 1rem; font-weight: 700; margin: 0.4rem 0 0;">Pašatzīmēts kā pabeigts</h2>
            @foreach ($completions as $completion)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.65rem 0.8rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                    <strong>{{ $completion->user?->name ?? 'Dzēsts lietotājs' }}</strong>
                    <span style="font-size: 0.85rem; color: #6b7280;">
                        {{ $completion->completed_at?->locale('lv')->translatedFormat('d.m.Y H:i') ?? 'Pabeigts' }}
                    </span>
                </div>
            @endforeach
        @endif
    @else
        <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Statuss</h2>
        <p style="font-size: 0.9rem; margin: 0;">
            @if ($myAssignment && ! $myAssignment->completed_at)
                @if ($myAssignment->due_at)
                    <x-filament::badge color="warning">Jāizpilda</x-filament::badge>
                    <span style="color: #6b7280; margin-left: 0.4rem;">Termiņš {{ $myAssignment->due_at->locale('lv')->translatedFormat('d.m.Y') }}</span>
                @elseif ($isPublic)
                    <x-filament::badge color="gray">Pieejams visiem</x-filament::badge>
                @else
                    <x-filament::badge color="gray">Jāizpilda</x-filament::badge>
                @endif
            @elseif ($myCompletedAt)
                <x-filament::badge color="success">Pabeigts</x-filament::badge>
                <span style="color: #6b7280; margin-left: 0.4rem;">{{ $myCompletedAt->locale('lv')->translatedFormat('d.m.Y H:i') }}</span>
            @elseif ($isPublic)
                <x-filament::badge color="gray">Pieejams visiem</x-filament::badge>
            @else
                <x-filament::badge color="gray">Jāizpilda</x-filament::badge>
            @endif
        </p>
    @endif
</section>
