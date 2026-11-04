<x-filament-panels::page>
    @php
        $report = $this->getReport();
        $years = $this->getYearOptions();
        $monthNames = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthNames[$month] = ucfirst(
                \Illuminate\Support\Carbon::create($this->year, $month, 1)->locale('lv')->translatedFormat('F')
            );
        }

        $periodLabel = $this->month > 0
            ? $monthNames[$this->month].' '.$this->year
            : (string) $this->year;

        $periodDescription = $this->month > 0
            ? $monthNames[$this->month].' '.$this->year
            : $this->year.'. gads';

        $money = fn (?float $value): string => $value === null
            ? 'Nav ievadīts'
            : number_format($value, 2, ',', ' ').' €';

        $missingParts = [];
        if ($report['undated_count'] > 0) {
            $missingParts[] = 'darījuma datums - '.$report['undated_count'];
        }
        if ($report['missing_commission'] > 0) {
            $missingParts[] = 'komisija - '.$report['missing_commission'];
        }

        $warningText = $missingParts === [] ? '' : 'Nepilnīgi dati: '.implode(', ', $missingParts).'.';
        if ($report['undated_count'] > 0) {
            $warningText .= ' Objekti bez darījuma datuma kopsummās nav iekļauti.';
        }
        if ($report['missing_commission'] > 0) {
            $warningText .= ' Komisija bez ievadītās summas kopsummā nav iekļauta.';
        }
    @endphp

    <style>
        .pdc-agent-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .pdc-agent-profile-avatar {
            flex: none;
            width: 4rem;
            height: 4rem;
            border-radius: 9999px;
            object-fit: cover;
            background: #f3f4f6;
        }
        .pdc-agent-profile-body {
            min-width: 0;
        }
        .pdc-agent-profile-name {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.3;
            color: #111827;
        }
        .pdc-agent-profile-meta {
            margin-top: 0.15rem;
            font-size: 0.85rem;
            color: #6b7280;
        }
        .pdc-agent-profile-contacts {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 1rem;
            margin-top: 0.55rem;
            font-size: 0.85rem;
            color: #374151;
        }
        .pdc-agent-profile-contacts a,
        .pdc-agent-profile-contacts span {
            display: inline-flex;
            align-items: flex-start;
            gap: 0.35rem;
            min-width: 0;
            color: inherit;
            text-decoration: none;
        }
        .pdc-agent-profile-contacts a {
            color: var(--pdc-primary, #285854);
            overflow-wrap: anywhere;
        }
        .pdc-agent-profile-contacts a:hover {
            text-decoration: underline;
        }
        .pdc-agent-profile-contacts i {
            flex: none;
            margin-top: 0.15rem;
            color: #9ca3af;
        }
        html.dark .pdc-agent-profile-name {
            color: #f3f4f6;
        }
        html.dark .pdc-agent-profile-meta {
            color: #9ca3af;
        }
        html.dark .pdc-agent-profile-contacts {
            color: #e5e7eb;
        }
        html.dark .pdc-agent-profile-contacts a {
            color: #98bcb8;
        }
        @media (max-width: 639px) {
            .pdc-agent-profile {
                align-items: flex-start;
                gap: 0.75rem;
            }
            .pdc-agent-profile-avatar {
                width: 3.25rem;
                height: 3.25rem;
            }
        }

        .pdc-agent-year {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4b5563;
            white-space: nowrap;
        }
        .pdc-agent-year select {
            padding: 0.35rem 0.6rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background: #ffffff;
            font-size: 0.85rem;
            color: #111827;
        }
        html.dark .pdc-agent-year {
            color: #9ca3af;
        }
        html.dark .pdc-agent-year select {
            background: #111827;
            border-color: #374151;
            color: #f3f4f6;
        }
        .pdc-agent-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
        }

        .pdc-agent-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (max-width: 767px) {
            .pdc-agent-stats {
                grid-template-columns: 1fr;
            }
        }

        /* Aktīvo objektu tabula dzīvo aģenta kartē: tā nedrīkst izskatīties
           kā vēl viena karte kartē. */
        .pdc-agent-card .fi-wi-table {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .pdc-agent-card .fi-ta-ctn {
            background: transparent !important;
            box-shadow: none !important;
        }
        html.dark .pdc-agent-card .fi-wi-table {
            border-color: #27303a;
        }

        .pdc-report-warning {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-top: 0.85rem;
            padding: 0.6rem 0.75rem;
            border: 1px solid #fed7aa;
            border-radius: 0.6rem;
            background: #fff7ed;
            font-size: 0.8rem;
            line-height: 1.4;
            color: #9a3412;
        }
        .pdc-report-warning i {
            flex: none;
            margin-top: 0.1rem;
        }
        html.dark .pdc-report-warning {
            background: rgba(249, 115, 22, 0.12);
            border-color: rgba(249, 115, 22, 0.4);
            color: #fdba74;
        }

        /* Mēnešu kopsavilkums paliek vienkārša tabula: tam nav ierakstu,
           meklēšanas vai lappušu. */
        .pdc-report-table-wrap {
            overflow-x: auto;
        }
        .pdc-report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .pdc-report-table th {
            padding: 0.5rem 0.6rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
            color: #6b7280;
        }
        .pdc-report-table td {
            padding: 0.55rem 0.6rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
            color: #111827;
        }
        .pdc-report-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .pdc-report-table tfoot td {
            padding: 0.6rem;
            border-top: 2px solid #e5e7eb;
            font-weight: 700;
            color: #111827;
        }
        .pdc-report-table .pdc-report-num {
            text-align: right;
            white-space: nowrap;
        }
        html.dark .pdc-report-table th {
            border-color: #27303a;
            color: #9ca3af;
        }
        html.dark .pdc-report-table td {
            border-color: #1f2831;
            color: #e5e7eb;
        }
        html.dark .pdc-report-table tfoot td {
            border-color: #27303a;
            color: #f3f4f6;
        }

        @media (max-width: 639px) {
            .pdc-report-table--monthly th,
            .pdc-report-table--monthly td {
                padding-right: 0.3rem;
                padding-left: 0.3rem;
                font-size: 0.8rem;
            }
            .pdc-report-table--monthly th {
                font-size: 0.66rem;
                letter-spacing: 0.02em;
            }
            .pdc-report-table--monthly .pdc-report-num {
                text-align: left !important;
            }
        }
    </style>

    <x-filament::section class="pdc-agent-card">
        <div class="pdc-agent-profile">
            <img
                class="pdc-agent-profile-avatar"
                src="{{ $record->avatar_url ?: asset('images/no-photo.svg') }}"
                alt=""
            >
            <div class="pdc-agent-profile-body">
                <div class="pdc-agent-profile-name">{{ $record->name }}</div>
                <div class="pdc-agent-profile-meta">
                    {{ collect([$record->role_label, $record->position])->filter()->unique()->implode(' · ') }}
                </div>
                <div class="pdc-agent-profile-contacts">
                    <a href="mailto:{{ $record->email }}">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                        <span>{{ $record->email }}</span>
                    </a>
                    @if (filled($record->phone))
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) $record->phone) }}">
                            <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            <span>{{ \App\Support\PhoneFormat::display($record->phone) }}</span>
                        </a>
                    @endif
                    @if (filled($record->office_address))
                        <span>
                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            <span>{{ $record->office_address }}</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @livewire(
            \App\Filament\Admin\Widgets\AgentActivePropertiesTable::class,
            ['agentId' => $record->getKey()],
            key('agent-active-'.$record->getKey())
        )
    </x-filament::section>

    <x-filament::section heading="Kopsavilkums">
        <x-slot name="afterHeader">
            <div class="pdc-agent-filters">
                <label class="pdc-agent-year" for="pdc-agent-year">
                    <span>Gads</span>
                    <select id="pdc-agent-year" wire:model.live="year">
                        @foreach ($years as $yearOption)
                            <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="pdc-agent-year" for="pdc-agent-month">
                    <span>Mēnesis</span>
                    <select id="pdc-agent-month" wire:model.live="month">
                        <option value="0">Visi mēneši</option>
                        @foreach ($monthNames as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}">{{ $monthName }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </x-slot>

        <div class="pdc-agent-stats">
            <div class="fi-wi-stats-overview-stat">
                <div class="fi-wi-stats-overview-stat-content">
                    <div class="fi-wi-stats-overview-stat-label-ctn">
                        <span class="fi-wi-stats-overview-stat-label">Pārdoti objekti · {{ $periodLabel }}</span>
                    </div>
                    <div class="fi-wi-stats-overview-stat-value">{{ $report['sold_count'] }}</div>
                </div>
            </div>
            <div class="fi-wi-stats-overview-stat">
                <div class="fi-wi-stats-overview-stat-content">
                    <div class="fi-wi-stats-overview-stat-label-ctn">
                        <span class="fi-wi-stats-overview-stat-label">Komisija · {{ $periodLabel }}</span>
                    </div>
                    <div class="fi-wi-stats-overview-stat-value">{{ $money($report['total_commission']) }}</div>
                </div>
            </div>
        </div>

        @if ($missingParts !== [])
            <div class="pdc-report-warning">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <span>{{ $warningText }}</span>
            </div>
        @endif
    </x-filament::section>

    @livewire(
        \App\Filament\Admin\Widgets\AgentSoldPropertiesTable::class,
        [
            'agentId' => $record->getKey(),
            'year' => $this->year,
            'month' => $this->month,
            'periodLabel' => $periodDescription,
        ],
        key('agent-sold-'.$record->getKey().'-'.$this->year.'-'.$this->month)
    )

    <x-filament::section heading="Komisija pa mēnešiem">
        <x-slot name="afterHeader">
            <label class="pdc-agent-year" for="pdc-agent-year-monthly">
                <span>Gads</span>
                <select id="pdc-agent-year-monthly" wire:model.live="year">
                    @foreach ($years as $yearOption)
                        <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                    @endforeach
                </select>
            </label>
        </x-slot>
        <div class="pdc-report-table-wrap">
            <table class="pdc-report-table pdc-report-table--monthly">
                <thead>
                    <tr>
                        <th>Mēnesis</th>
                        <th class="pdc-report-num">Pārdoti objekti</th>
                        <th class="pdc-report-num">Komisija kopā</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['monthly'] as $monthNumber => $month)
                        <tr wire:key="month-{{ $monthNumber }}">
                            <td>{{ $monthNames[$monthNumber] }}</td>
                            <td class="pdc-report-num">{{ $month['count'] }}</td>
                            <td class="pdc-report-num">{{ $money($month['commission']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Kopā {{ $this->year }}</td>
                        <td class="pdc-report-num">{{ $report['year_sold_count'] }}</td>
                        <td class="pdc-report-num">{{ $money($report['year_total_commission']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
