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

        $hasActiveFilters = filled($activeSearch) || filled($activeCategory) || filled($activeStatus);
        $hasSoldFilters = filled($soldSearch) || filled($soldSource);
        $soldProperties = $report['sold'];
    @endphp

    <style>
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

        .pdc-report-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
        }
        .pdc-report-control {
            padding: 0.4rem 0.6rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background: #ffffff;
            font-size: 0.82rem;
            color: #111827;
        }
        .pdc-report-control:focus {
            outline: none;
            border-color: var(--pdc-primary, #285854);
            box-shadow: 0 0 0 2px rgba(40, 88, 84, 0.18);
        }
        .pdc-report-search {
            flex: 1 1 15rem;
            min-width: 0;
        }
        .pdc-report-toolbar select {
            flex: 0 1 auto;
            max-width: 100%;
        }
        .pdc-report-clear {
            padding: 0.4rem 0.2rem;
            border: 0;
            background: transparent;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--pdc-primary, #285854);
            cursor: pointer;
        }
        .pdc-report-clear:hover {
            text-decoration: underline;
        }
        html.dark .pdc-report-control {
            background: #111827;
            border-color: #374151;
            color: #f3f4f6;
        }
        html.dark .pdc-report-clear {
            color: #98bcb8;
        }

        .pdc-report-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .pdc-report-pager-buttons {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .pdc-report-page-btn {
            padding: 0.3rem 0.55rem;
            border: 1px solid #d1d5db;
            border-radius: 0.4rem;
            background: #ffffff;
            font-size: 0.78rem;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
        }
        .pdc-report-page-btn:disabled {
            opacity: 0.45;
            cursor: default;
        }
        html.dark .pdc-report-pager {
            color: #9ca3af;
        }
        html.dark .pdc-report-page-btn {
            background: #111827;
            border-color: #374151;
            color: #e5e7eb;
        }

        .pdc-agent-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
        }
        .pdc-agent-stat {
            padding: 0.9rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #f9fafb;
        }
        .pdc-agent-stat-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
        }
        .pdc-agent-stat-value {
            display: block;
            margin-top: 0.15rem;
            font-size: 1.45rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--pdc-primary, #285854);
        }
        html.dark .pdc-agent-stat {
            border-color: #27303a;
            background: #0b0f14;
        }
        html.dark .pdc-agent-stat-label {
            color: #9ca3af;
        }
        html.dark .pdc-agent-stat-value {
            color: #98bcb8;
        }
        @media (max-width: 767px) {
            .pdc-agent-stats {
                grid-template-columns: 1fr;
            }
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

        .pdc-report-link {
            font-weight: 600;
            color: var(--pdc-primary, #285854);
            text-decoration: none;
        }
        .pdc-report-link:hover {
            text-decoration: underline;
        }
        html.dark .pdc-report-link {
            color: #98bcb8;
        }
        .pdc-report-muted {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.75rem;
            color: #9ca3af;
        }
        .pdc-report-missing {
            display: inline-block;
            padding: 0.1rem 0.45rem;
            border: 1px solid #fed7aa;
            border-radius: 0.35rem;
            background: #fff7ed;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
            color: #9a3412;
        }
        html.dark .pdc-report-missing {
            background: rgba(249, 115, 22, 0.12);
            border-color: rgba(249, 115, 22, 0.4);
            color: #fdba74;
        }

        /* Uz mobilā platuma rindas kļūst par kartītēm: kolonnu virsraksti
           pārnesti uz katras šūnas data-label, lai tabulas nav jāritina
           horizontāli. Mēnešu tabula paliek tabula - tai pietiek vietas. */
        @media (max-width: 639px) {
            .pdc-report-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .pdc-report-search {
                flex: 1 1 auto;
            }
            .pdc-report-toolbar select {
                width: 100%;
            }
            .pdc-report-clear {
                align-self: flex-start;
            }
            .pdc-report-table--cards thead {
                display: none;
            }
            .pdc-report-table--cards,
            .pdc-report-table--cards tbody,
            .pdc-report-table--cards tfoot,
            .pdc-report-table--cards tr,
            .pdc-report-table--cards td {
                display: block;
                width: 100%;
            }
            .pdc-report-table--cards tbody tr {
                margin-bottom: 0.6rem;
                padding: 0.7rem 0.8rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.65rem;
                background: #ffffff;
            }
            .pdc-report-table--cards tbody tr:last-child {
                margin-bottom: 0;
            }
            .pdc-report-table--cards tbody td {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 1rem;
                padding: 0.18rem 0;
                border: 0;
            }
            .pdc-report-table--cards tbody td::before,
            .pdc-report-table--cards tfoot td[data-label]::before {
                content: attr(data-label);
                flex: none;
                font-size: 0.7rem;
                font-weight: 600;
                letter-spacing: 0.03em;
                text-transform: uppercase;
                color: #6b7280;
            }
            .pdc-report-table--cards tbody td.pdc-report-object {
                display: block;
                padding: 0 0 0.35rem;
            }
            .pdc-report-table--cards tbody td.pdc-report-object::before {
                content: none;
            }
            .pdc-report-table--cards tfoot tr {
                padding: 0.7rem 0.8rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.65rem;
                background: #f9fafb;
            }
            .pdc-report-table--cards tfoot td {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                padding: 0.18rem 0;
                border: 0;
            }
            .pdc-report-table--cards tfoot td.pdc-report-total-label {
                display: block;
                padding: 0 0 0.2rem;
                font-size: 0.85rem;
            }
            .pdc-report-table--cards tfoot td:empty {
                display: none;
            }
            html.dark .pdc-report-table--cards tbody tr {
                border-color: #27303a;
                background: #0b0f14;
            }
            html.dark .pdc-report-table--cards tbody td::before,
            html.dark .pdc-report-table--cards tfoot td[data-label]::before {
                color: #9ca3af;
            }
            html.dark .pdc-report-table--cards tfoot tr {
                border-color: #27303a;
                background: #0b0f14;
            }

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
        }
    </style>

    <x-filament::section
        heading="Kopsavilkums"
        description="Objekti pārdošanā, pārdotie objekti un komisija {{ $this->year }}. gadā"
    >
        <x-slot name="afterHeader">
            <label class="pdc-agent-year" for="pdc-agent-year">
                <span>Gads</span>
                <select id="pdc-agent-year" wire:model.live="year">
                    @foreach ($years as $yearOption)
                        <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                    @endforeach
                </select>
            </label>
        </x-slot>

        <div class="pdc-agent-stats">
            <div class="pdc-agent-stat">
                <span class="pdc-agent-stat-label">Objekti pārdošanā</span>
                <span class="pdc-agent-stat-value">{{ $report['active_total'] }}</span>
            </div>
            <div class="pdc-agent-stat">
                <span class="pdc-agent-stat-label">Pārdoti objekti · {{ $this->year }}</span>
                <span class="pdc-agent-stat-value">{{ $report['sold_count'] }}</span>
            </div>
            <div class="pdc-agent-stat">
                <span class="pdc-agent-stat-label">Komisija · {{ $this->year }}</span>
                <span class="pdc-agent-stat-value">{{ $money($report['total_commission']) }}</span>
            </div>
        </div>

        @if ($missingParts !== [])
            <div class="pdc-report-warning">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <span>{{ $warningText }}</span>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Objekti pārdošanā">
        <div class="pdc-report-toolbar">
            <input
                type="search"
                class="pdc-report-control pdc-report-search"
                placeholder="Meklēt objektu"
                aria-label="Meklēt aktīvos objektus"
                wire:model.live.debounce.400ms="activeSearch"
            >
            <select
                class="pdc-report-control"
                aria-label="Filtrēt pēc tipa"
                wire:model.live="activeCategory"
            >
                <option value="">Visi tipi</option>
                @foreach (\App\Models\CrmProperty::CATEGORIES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select
                class="pdc-report-control"
                aria-label="Filtrēt pēc statusa"
                wire:model.live="activeStatus"
            >
                <option value="">Visi statusi</option>
                @foreach (array_intersect_key(\App\Models\CrmProperty::STATUSES, ['published' => true, 'reserved' => true]) as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @if ($hasActiveFilters)
                <button type="button" class="pdc-report-clear" wire:click="clearActiveFilters">Notīrīt</button>
            @endif
        </div>

        @if ($report['active']->isEmpty())
            <div class="pdc-empty-state">
                <div class="pdc-empty-state-inner">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    <span>{{ $hasActiveFilters ? 'Nav rezultātu' : 'Aktīvu objektu nav' }}</span>
                </div>
            </div>
        @else
            <div class="pdc-report-table-wrap">
                <table class="pdc-report-table pdc-report-table--cards">
                    <thead>
                        <tr>
                            <th>Objekts</th>
                            <th>Tips</th>
                            <th class="pdc-report-num">Cena</th>
                            <th>Statuss</th>
                            <th>Pievienots</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['active'] as $property)
                            <tr wire:key="active-{{ $property->id }}">
                                <td class="pdc-report-object">
                                    <a
                                        class="pdc-report-link"
                                        href="{{ \App\Filament\Admin\Resources\CrmPropertyResource::getUrl('view', ['record' => $property]) }}"
                                    >{{ $property->title }}</a>
                                    @if ($property->city || $property->address)
                                        <span class="pdc-report-muted">
                                            {{ collect([$property->city, $property->address])->filter()->implode(', ') }}
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Tips">{{ $property->category ?: '-' }}</td>
                                <td class="pdc-report-num" data-label="Cena">{{ $property->price_display }}</td>
                                <td data-label="Statuss"><x-filament::badge color="gray">{{ $property->status_label }}</x-filament::badge></td>
                                <td data-label="Pievienots">{{ $property->created_at?->format('d.m.Y') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($report['active']->hasPages())
                <div class="pdc-report-pager">
                    <span>Rāda {{ $report['active']->firstItem() }}-{{ $report['active']->lastItem() }} no {{ $report['active']->total() }}</span>
                    <div class="pdc-report-pager-buttons">
                        <button
                            type="button"
                            class="pdc-report-page-btn"
                            wire:click="setActivePage({{ $report['active']->currentPage() - 1 }})"
                            @disabled($report['active']->onFirstPage())
                        >Iepriekšējā</button>
                        <span>{{ $report['active']->currentPage() }} / {{ $report['active']->lastPage() }}</span>
                        <button
                            type="button"
                            class="pdc-report-page-btn"
                            wire:click="setActivePage({{ $report['active']->currentPage() + 1 }})"
                            @disabled(! $report['active']->hasMorePages())
                        >Nākamā</button>
                    </div>
                </div>
            @endif
        @endif
    </x-filament::section>

    <x-filament::section heading="Pārdotie objekti un saņemtā komisija">
        <div class="pdc-report-toolbar">
            <input
                type="search"
                class="pdc-report-control pdc-report-search"
                placeholder="Meklēt objektu"
                aria-label="Meklēt pārdotos objektus"
                wire:model.live.debounce.400ms="soldSearch"
            >
            <select
                class="pdc-report-control"
                aria-label="Filtrēt pēc pieteikuma avota"
                wire:model.live="soldSource"
            >
                <option value="">Visi avoti</option>
                @foreach (\App\Models\CrmProperty::LEAD_SOURCES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @if ($hasSoldFilters)
                <button type="button" class="pdc-report-clear" wire:click="clearSoldFilters">Notīrīt</button>
            @endif
        </div>

        @if ($soldProperties->isEmpty())
            <div class="pdc-empty-state">
                <div class="pdc-empty-state-inner">
                    <i class="fa-solid fa-euro-sign" aria-hidden="true"></i>
                    <span>{{ $hasSoldFilters ? 'Nav rezultātu' : 'Pārdoto objektu nav' }}</span>
                </div>
            </div>
        @else
            <div class="pdc-report-table-wrap">
                <table class="pdc-report-table pdc-report-table--cards">
                    <thead>
                        <tr>
                            <th>Objekts</th>
                            <th>Darījuma datums</th>
                            <th class="pdc-report-num">Pārdošanas cena</th>
                            <th class="pdc-report-num">Komisija kopā</th>
                            <th class="pdc-report-num">Aģenta %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($soldProperties as $property)
                            <tr wire:key="sold-{{ $property->id }}">
                                <td class="pdc-report-object">
                                    <a
                                        class="pdc-report-link"
                                        href="{{ \App\Filament\Admin\Resources\CrmPropertyResource::getUrl('view', ['record' => $property]) }}"
                                    >{{ $property->title }}</a>
                                    @if ($property->city || $property->address)
                                        <span class="pdc-report-muted">
                                            {{ collect([$property->city, $property->address])->filter()->implode(', ') }}
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Darījuma datums">
                                    @if ($property->sold_at)
                                        {{ $property->sold_at->format('d.m.Y') }}
                                    @else
                                        <span class="pdc-report-missing">Nav ievadīts</span>
                                    @endif
                                </td>
                                <td class="pdc-report-num" data-label="Pārdošanas cena">
                                    @if ($property->final_price_eur === null)
                                        <span class="pdc-report-missing">Nav ievadīts</span>
                                    @else
                                        {{ $money((float) $property->final_price_eur) }}
                                    @endif
                                </td>
                                <td class="pdc-report-num" data-label="Komisija kopā">
                                    @if ($property->commission_eur === null)
                                        <span class="pdc-report-missing">Nav ievadīts</span>
                                    @else
                                        {{ $money((float) $property->commission_eur) }}
                                    @endif
                                </td>
                                <td class="pdc-report-num" data-label="Aģenta %">
                                    @if ($property->commission_percent === null)
                                        <span class="pdc-report-missing">Nav ievadīts</span>
                                    @else
                                        {{ number_format($property->commission_percent, 2, ',', ' ') }} %
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="pdc-report-total-label">Kopā {{ $this->year }}</td>
                            <td class="pdc-report-num" data-label="Pārdošanas cena">{{ $money($report['table_total_price']) }}</td>
                            <td class="pdc-report-num" data-label="Komisija kopā">{{ $money($report['table_total_commission']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($report['sold']->hasPages())
                <div class="pdc-report-pager">
                    <span>Rāda {{ $report['sold']->firstItem() }}-{{ $report['sold']->lastItem() }} no {{ $report['sold']->total() }}</span>
                    <div class="pdc-report-pager-buttons">
                        <button
                            type="button"
                            class="pdc-report-page-btn"
                            wire:click="setSoldPage({{ $report['sold']->currentPage() - 1 }})"
                            @disabled($report['sold']->onFirstPage())
                        >Iepriekšējā</button>
                        <span>{{ $report['sold']->currentPage() }} / {{ $report['sold']->lastPage() }}</span>
                        <button
                            type="button"
                            class="pdc-report-page-btn"
                            wire:click="setSoldPage({{ $report['sold']->currentPage() + 1 }})"
                            @disabled(! $report['sold']->hasMorePages())
                        >Nākamā</button>
                    </div>
                </div>
            @endif
        @endif
    </x-filament::section>

    <x-filament::section heading="Komisija pa mēnešiem">
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
                        <td class="pdc-report-num">{{ $report['sold_count'] }}</td>
                        <td class="pdc-report-num">{{ $money($report['total_commission']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
