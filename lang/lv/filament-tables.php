<?php

return [

    'table' => [

        'default_model_label' => 'Ieraksts',

        'empty' => [
            'heading' => 'Nav ierakstu',
            'description' => 'Šeit pagaidām nav nekā.',
        ],

        'fields' => [
            'search' => [
                'indicator' => 'Meklēt',
            ],
        ],

        'actions' => [
            'filter' => [
                'label' => 'Filtri',
            ],
            'column_manager' => [
                'label' => 'Kolonnas',
            ],
            'open_bulk_actions' => [
                'label' => 'Darbības',
            ],
            'group' => [
                'label' => 'Grupēt',
            ],
            'enable_reordering' => [
                'label' => 'Iespējot kārtošanu',
            ],
            'disable_reordering' => [
                'label' => 'Atspējot kārtošanu',
            ],
        ],

        'column_manager' => [
            'actions' => [
                'apply' => [
                    'label' => 'Pielietot',
                ],
                'reset' => [
                    'label' => 'Atiestatīt',
                ],
            ],
        ],

        'columns' => [
            'text' => [
                'actions' => [
                    'collapse_list' => 'Rādīt mazāk',
                    'expand_list' => 'Rādīt vairāk',
                ],
                'more_list_items' => 'vēl :count',
            ],
            'select' => [
                'placeholder' => 'Izvēlieties opciju',
                'no_options_message' => 'Nav pieejamu opciju',
                'no_search_results_message' => 'Nekas netika atrasts',
                'search_prompt' => 'Ierakstiet, lai meklētu...',
                'searching_message' => 'Meklēšana...',
            ],
            'icon' => [
                'boolean' => [
                    '' => '',
                ],
            ],
        ],

        'filters' => [
            'actions' => [
                'apply' => [
                    'label' => 'Pielietot',
                ],
                'reset' => [
                    'label' => 'Atiestatīt',
                ],
                'remove_all' => [
                    'label' => 'Noņemt visus',
                    'tooltip' => 'Noņemt visus filtrus',
                ],
            ],
            'select' => [
                'placeholder' => 'Izvēlieties...',
                'relationship' => [
                    'empty_option_label' => 'Visi',
                ],
            ],
            'multi_select' => [
                'placeholder' => 'Izvēlieties...',
            ],
            'trashed' => [
                'label' => 'Dzēstie',
                'only_trashed' => 'Tikai dzēstie',
                'with_trashed' => 'Ar dzēstajiem',
                'without_trashed' => 'Bez dzēstajiem',
            ],
        ],

        'summary' => [
            'summarizers' => [
                'count' => [
                    'label' => 'Skaits',
                ],
                'sum' => [
                    'label' => 'Kopā',
                ],
                'average' => [
                    'label' => 'Vidēji',
                ],
            ],
        ],

    ],

];
