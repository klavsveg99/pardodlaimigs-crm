@php
    $visibleAssignments = $isAdmin
        ? $assignments
        : $assignments->where('assigned_user_id', auth()->id());
@endphp

<section style="display: grid; gap: 0.6rem;">
    <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">{{ $isAdmin ? 'Piešķīrumi' : 'Jūsu piešķīrumi' }}</h2>
    @if ($visibleAssignments->isEmpty())
        <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">Materiāls vēl nav piešķirts nevienam aģentam.</p>
    @endif
    @foreach ($visibleAssignments as $assignment)
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.65rem 0.8rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div style="display: grid; gap: 0.15rem;">
                @if ($isAdmin)
                    <strong>{{ $assignment->assignedTo?->name ?? 'Dzēsts lietotājs' }}</strong>
                @endif
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
</section>
