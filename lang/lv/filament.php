<?php

return [

    'components' => [

        'pagination' => [

            'label' => 'Lappošanas navigācija',

            'overview' => '{1} Rāda 1 rezultātu|[2,*] Rāda :first līdz :last no :total rezultātiem',

            'fields' => [
                'records_per_page' => [
                    'label' => 'Lappusē',
                    'options' => [
                        'all' => 'Visus',
                    ],
                ],
            ],

            'actions' => [
                'first' => [
                    'label' => 'Pirmā',
                ],
                'go_to_page' => [
                    'label' => 'Iet uz lapu :page',
                ],
                'last' => [
                    'label' => 'Pēdējā',
                ],
                'next' => [
                    'label' => 'Nākamā',
                ],
                'previous' => [
                    'label' => 'Iepriekšējā',
                ],
            ],

        ],

    ],

];
