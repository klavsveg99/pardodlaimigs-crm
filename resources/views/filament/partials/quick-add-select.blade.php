{{--
    Filament stila meklējams select kalendāra ātrajai pievienošanai.
    Izmanto Filament select komponenti (selectFormComponent) ar statiskām
    opcijām no calendarQuickAdd::optionsFor(<field>). Atlasīto vērtību
    nolasa calendarQuickAdd::readSelects() saglabāšanas brīdī.

    $field      - lauka atslēga (piem. client_id)
    $placeholder
    $state      - JS izteiksme sākuma vērtībai (pēc noklusējuma 'null')
    $searchable - vai meklējams (pēc noklusējuma true)
--}}
@php
    $state ??= 'null';
    $searchable ??= true;
    $placeholder ??= 'Atlasiet';
@endphp

<div
    data-pdc-select="{{ $field }}"
    class="fi-fo-select fi-input-wrp"
    x-on:focus-input.stop="$el.querySelector('.fi-select-input-btn')?.focus()"
>
    <div class="fi-input-wrp-content-ctn">
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select', 'filament/forms') }}"
            x-data="selectFormComponent({
                canOptionLabelsWrap: true,
                canSelectPlaceholder: true,
                clearButtonLabel: 'Notīrīt',
                hasDynamicOptions: false,
                hasDynamicSearchResults: false,
                hasInitialNoOptionsMessage: true,
                id: 'pdc-qa-{{ $field }}',
                initialOptionLabel: null,
                initialOptionLabels: [],
                initialState: {{ $state }},
                isAutofocused: false,
                isDisabled: false,
                isHtmlAllowed: false,
                isMultiple: false,
                isReorderable: false,
                isSearchable: {{ $searchable ? 'true' : 'false' }},
                livewireId: null,
                loadingMessage: 'Ielādē...',
                maxItems: null,
                maxItemsMessage: '',
                noOptionsMessage: 'Nav atrasti ieraksti.',
                noSearchResultsMessage: 'Nekas neatbilst meklēšanai.',
                options: optionsFor('{{ $field }}'),
                optionsLimit: 50,
                placeholder: '{{ $placeholder }}',
                position: null,
                removeButtonLabel: 'Noņemt',
                searchDebounce: 0,
                searchingMessage: 'Meklē...',
                searchLabel: 'Meklēt',
                searchPrompt: 'Meklēt...',
                searchableOptionFields: ['label'],
                state: {{ $state }},
                statePath: '{{ $field }}',
            })"
        >
            <div x-ref="select"></div>
        </div>
    </div>
</div>
