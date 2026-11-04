@php
    $date = fn ($value) => $value?->locale('lv')->translatedFormat('d.m.Y');
    $stars = fn (?int $rating): string => $rating ? str_repeat('★', $rating).str_repeat('☆', 5 - $rating) : '-';
@endphp

<x-filament::section
    heading="Zināšanu bāze"
    description="Kursi un materiāli, kas redzami visiem vai piešķirti lietotājam"
>
    <style>
        .pdc-kb-recap { display: grid; gap: 1.25rem; }
        .pdc-kb-recap-summary { display: flex; flex-wrap: wrap; gap: 0.4rem 1.25rem; font-size: 0.8rem; color: #6b7280; }
        .pdc-kb-recap-summary strong { color: #111827; }
        .pdc-kb-recap-title { font-size: 0.9rem; font-weight: 700; color: #111827; margin: 0 0 0.5rem; }
        .pdc-kb-recap-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .pdc-kb-recap-table th { padding: 0.4rem 0.5rem; border-bottom: 1px solid #e5e7eb; font-size: 0.68rem; font-weight: 600; letter-spacing: 0.03em; text-align: left; text-transform: uppercase; white-space: nowrap; color: #6b7280; }
        .pdc-kb-recap-table td { padding: 0.5rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; color: #111827; }
        .pdc-kb-recap-table tr:last-child td { border-bottom: 0; }
        .pdc-kb-recap-progress { display: flex; align-items: center; gap: 0.5rem; min-width: 9rem; }
        .pdc-kb-recap-bar { flex: 1 1 auto; height: 0.35rem; min-width: 4rem; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
        .pdc-kb-recap-bar > span { display: block; height: 100%; border-radius: inherit; background: var(--pdc-primary, #285854); }
        .pdc-kb-recap-count { font-size: 0.72rem; color: #6b7280; white-space: nowrap; }
        .pdc-kb-recap-stars { color: #d97706; letter-spacing: 0.05em; white-space: nowrap; }
        .pdc-kb-recap-badge { display: inline-flex; align-items: center; padding: 0.12rem 0.45rem; border: 1px solid #e5e7eb; border-radius: 9999px; font-size: 0.68rem; font-weight: 700; white-space: nowrap; color: #4b5563; }
        .pdc-kb-recap-badge--done { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
        .pdc-kb-recap-badge--overdue { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
        .pdc-kb-recap-empty { font-size: 0.82rem; color: #6b7280; margin: 0; }
        .pdc-kb-recap-wrap { overflow-x: auto; }
        .pdc-kb-recap-table td a { color: var(--pdc-primary, #285854); text-decoration: none; }
        .pdc-kb-recap-table td a:hover { text-decoration: underline; }
        html.dark .pdc-kb-recap-table td a { color: #98bcb8; }
        @media (max-width: 639px) {
            .pdc-kb-recap-table thead { display: none; }
            .pdc-kb-recap-table,
            .pdc-kb-recap-table tbody,
            .pdc-kb-recap-table tr,
            .pdc-kb-recap-table td { display: block; width: 100%; }
            .pdc-kb-recap-table tr { padding: 0.6rem 0; border-bottom: 1px solid #f3f4f6; }
            .pdc-kb-recap-table tr:last-child { border-bottom: 0; }
            .pdc-kb-recap-table td { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.18rem 0; border: 0; }
            .pdc-kb-recap-table td::before { content: attr(data-label); font-size: 0.68rem; font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase; color: #9ca3af; }
            .pdc-kb-recap-progress { flex: 1 1 auto; min-width: 0; }
        }
        html.dark .pdc-kb-recap-summary { color: #9ca3af; }
        html.dark .pdc-kb-recap-summary strong { color: #f3f4f6; }
        html.dark .pdc-kb-recap-title { color: #f3f4f6; }
        html.dark .pdc-kb-recap-table th { border-color: #27303a; color: #9ca3af; }
        html.dark .pdc-kb-recap-table td { border-color: #1f2831; color: #e5e7eb; }
        html.dark .pdc-kb-recap-table tr { border-color: #1f2831; }
        html.dark .pdc-kb-recap-bar { background: #27303a; }
        html.dark .pdc-kb-recap-count { color: #9ca3af; }
        html.dark .pdc-kb-recap-badge { border-color: #374151; color: #d1d5db; }
        html.dark .pdc-kb-recap-badge--done { border-color: #064e3b; background: #052e25; color: #6ee7b7; }
        html.dark .pdc-kb-recap-badge--overdue { border-color: #7f1d1d; background: #2a0f0f; color: #fca5a5; }
        html.dark .pdc-kb-recap-empty { color: #9ca3af; }
    </style>

    <div class="pdc-kb-recap">
        <div class="pdc-kb-recap-summary">
            <span>Piešķirti kursi: <strong>{{ count($knowledge['assigned']) }}</strong></span>
            <span>Pabeigti kursi: <strong>{{ $knowledge['completed'] }}</strong></span>
            <span>Materiāli: <strong>{{ count($knowledge['materials']) }}</strong></span>
            <span>Pabeigti materiāli: <strong>{{ $knowledge['materials_completed'] }}</strong></span>
        </div>

        <div>
            <h3 class="pdc-kb-recap-title">Piešķirtie kursi</h3>

            @if ($knowledge['assigned'] === [])
                <p class="pdc-kb-recap-empty">Nav piešķirtu kursu.</p>
            @else
                <div class="pdc-kb-recap-wrap">
                    <table class="pdc-kb-recap-table">
                        <thead>
                            <tr>
                                <th>Kurss</th>
                                <th>Progress</th>
                                <th>Termiņš</th>
                                <th>Statuss</th>
                                <th>Vērtējums</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($knowledge['assigned'] as $course)
                                @php
                                    $overdue = ! $course['completed_at']
                                        && $course['due_at'] !== null
                                        && $course['due_at']->isPast();
                                @endphp
                                <tr wire:key="kb-recap-assigned-{{ $loop->index }}">
                                    <td data-label="Kurss"><strong>{{ $course['name'] }}</strong></td>
                                    <td data-label="Progress">
                                        <div class="pdc-kb-recap-progress">
                                            <div class="pdc-kb-recap-bar"><span style="width: {{ $course['stats']['percent'] }}%"></span></div>
                                            <span class="pdc-kb-recap-count">{{ $course['stats']['completed'] }}/{{ $course['stats']['total'] }} · {{ $course['stats']['percent'] }}%</span>
                                        </div>
                                    </td>
                                    <td data-label="Termiņš">
                                        @if ($course['due_at'])
                                            <span class="pdc-kb-recap-badge {{ $overdue ? 'pdc-kb-recap-badge--overdue' : '' }}">
                                                {{ $date($course['due_at']) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td data-label="Statuss">
                                        <span class="pdc-kb-recap-badge {{ $course['completed_at'] ? 'pdc-kb-recap-badge--done' : '' }}">
                                            {{ $course['completed_at'] ? 'Pabeigts' : 'Jāizpilda' }}
                                        </span>
                                    </td>
                                    <td data-label="Vērtējums"><span class="pdc-kb-recap-stars">{{ $stars($course['rating']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div>
            <h3 class="pdc-kb-recap-title">Materiāli</h3>

            @if ($knowledge['materials'] === [])
                <p class="pdc-kb-recap-empty">Nav redzamu vai piešķirtu materiālu.</p>
            @else
                <div class="pdc-kb-recap-wrap">
                    <table class="pdc-kb-recap-table">
                        <thead>
                            <tr>
                                <th>Materiāls</th>
                                <th>Pieejamība</th>
                                <th>Termiņš</th>
                                <th>Statuss</th>
                                <th>Vērtējums</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($knowledge['materials'] as $material)
                                @php
                                    $completed = $material['completed_at'] !== null;
                                    $overdue = ! $completed
                                        && $material['due_at'] !== null
                                        && $material['due_at']->isPast();
                                    $status = $completed ? 'Pabeigts' : ($material['assigned'] ? 'Jāizpilda' : 'Pieejams');
                                @endphp
                                <tr wire:key="kb-recap-material-{{ $loop->index }}">
                                    <td data-label="Materiāls">
                                        <a href="{{ $material['url'] }}"><strong>{{ $material['name'] }}</strong></a>
                                    </td>
                                    <td data-label="Pieejamība">
                                        @if ($material['public'])
                                            <span class="pdc-kb-recap-badge">Visiem</span>
                                        @endif
                                        @if ($material['assigned'])
                                            <span class="pdc-kb-recap-badge">Piešķirts</span>
                                        @endif
                                    </td>
                                    <td data-label="Termiņš">
                                        @if ($material['due_at'])
                                            <span class="pdc-kb-recap-badge {{ $overdue ? 'pdc-kb-recap-badge--overdue' : '' }}">
                                                {{ $date($material['due_at']) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td data-label="Statuss">
                                        <span class="pdc-kb-recap-badge {{ $completed ? 'pdc-kb-recap-badge--done' : '' }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td data-label="Vērtējums"><span class="pdc-kb-recap-stars">{{ $stars($material['rating']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament::section>
