<?php

return [

    'associate' => [
        'single' => [
            'label' => 'Saistīt',
            'modal' => [
                'heading' => 'Saistīt :label',
                'actions' => [
                    'associate' => [
                        'label' => 'Saistīt',
                    ],
                    'associate_another' => [
                        'label' => 'Saistīt un vēl vienu',
                    ],
                ],
                'fields' => [
                    'record_id' => [
                        'label' => 'Ieraksts',
                    ],
                ],
            ],
            'notifications' => [
                'associated' => [
                    'title' => 'Saistīts',
                ],
            ],
        ],
    ],

    'attach' => [
        'single' => [
            'label' => 'Pievienot',
            'modal' => [
                'heading' => 'Pievienot :label',
                'actions' => [
                    'attach' => [
                        'label' => 'Pievienot',
                    ],
                    'attach_another' => [
                        'label' => 'Pievienot un vēl vienu',
                    ],
                ],
                'fields' => [
                    'record_id' => [
                        'label' => 'Ieraksts',
                    ],
                ],
            ],
            'notifications' => [
                'attached' => [
                    'title' => 'Pievienots',
                ],
            ],
        ],
    ],

    'create' => [
        'single' => [
            'label' => 'Izveidot',
            'modal' => [
                'heading' => 'Izveidot :label',
                'actions' => [
                    'create' => [
                        'label' => 'Izveidot',
                    ],
                    'create_another' => [
                        'label' => 'Izveidot un vēl vienu',
                    ],
                ],
            ],
            'notifications' => [
                'created' => [
                    'title' => 'Izveidots',
                ],
            ],
        ],
    ],

    'delete' => [
        'single' => [
            'label' => 'Dzēst',
            'modal' => [
                'heading' => 'Dzēst :label',
                'actions' => [
                    'delete' => [
                        'label' => 'Dzēst',
                    ],
                ],
            ],
            'notifications' => [
                'deleted' => [
                    'title' => 'Dzēsts',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Dzēst izvēlētos',
            'modal' => [
                'heading' => 'Dzēst izvēlētos ierakstus',
                'actions' => [
                    'delete' => [
                        'label' => 'Dzēst',
                    ],
                ],
            ],
            'notifications' => [
                'deleted' => [
                    'title' => 'Ieraksti dzēsti',
                ],
                'deleted_none' => [
                    'title' => 'Nekas nav dzēsts',
                    'missing_authorization_failure_message' => 'Dažus ierakstus nevarēja dzēst, jo jums nav atļaujas.',
                    'missing_processing_failure_message' => 'Dažus ierakstus nevarēja dzēst.',
                ],
                'deleted_partial' => [
                    'title' => 'Daži ieraksti dzēsti',
                    'missing_authorization_failure_message' => 'Dažus ierakstus nevarēja dzēst, jo jums nav atļaujas.',
                    'missing_processing_failure_message' => 'Dažus ierakstus nevarēja dzēst.',
                ],
            ],
        ],
    ],

    'detach' => [
        'single' => [
            'label' => 'Noņemt',
            'modal' => [
                'heading' => 'Noņemt :label',
                'actions' => [
                    'detach' => [
                        'label' => 'Noņemt',
                    ],
                ],
            ],
            'notifications' => [
                'detached' => [
                    'title' => 'Noņemts',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Noņemt izvēlētos',
            'modal' => [
                'heading' => 'Noņemt izvēlētos ierakstus',
                'actions' => [
                    'detach' => [
                        'label' => 'Noņemt',
                    ],
                ],
            ],
            'notifications' => [
                'detached' => [
                    'title' => 'Ieraksti noņemti',
                ],
            ],
        ],
    ],

    'dissociate' => [
        'single' => [
            'label' => 'Atsaistīt',
            'modal' => [
                'heading' => 'Atsaistīt :label',
                'actions' => [
                    'dissociate' => [
                        'label' => 'Atsaistīt',
                    ],
                ],
            ],
            'notifications' => [
                'dissociated' => [
                    'title' => 'Atsaistīts',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Atsaistīt izvēlētos',
            'modal' => [
                'heading' => 'Atsaistīt izvēlētos ierakstus',
                'actions' => [
                    'dissociate' => [
                        'label' => 'Atsaistīt',
                    ],
                ],
            ],
            'notifications' => [
                'dissociated' => [
                    'title' => 'Ieraksti atsaistīti',
                ],
            ],
        ],
    ],

    'edit' => [
        'single' => [
            'label' => 'Rediģēt',
            'modal' => [
                'heading' => 'Rediģēt :label',
                'actions' => [
                    'save' => [
                        'label' => 'Saglabāt',
                    ],
                ],
            ],
            'notifications' => [
                'saved' => [
                    'title' => 'Saglabāts',
                ],
            ],
        ],
    ],

    'force-delete' => [
        'single' => [
            'label' => 'Pilnībā dzēst',
            'modal' => [
                'heading' => 'Pilnībā dzēst :label',
                'actions' => [
                    'delete' => [
                        'label' => 'Dzēst',
                    ],
                ],
            ],
            'notifications' => [
                'deleted' => [
                    'title' => 'Pilnībā dzēsts',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Pilnībā dzēst izvēlētos',
            'modal' => [
                'heading' => 'Pilnībā dzēst izvēlētos ierakstus',
                'actions' => [
                    'delete' => [
                        'label' => 'Dzēst',
                    ],
                ],
            ],
            'notifications' => [
                'deleted' => [
                    'title' => 'Ieraksti pilnībā dzēsti',
                ],
                'deleted_none' => [
                    'title' => 'Nekas nav dzēsts',
                ],
                'deleted_partial' => [
                    'title' => 'Daži ieraksti dzēsti',
                ],
            ],
        ],
    ],

    'restore' => [
        'single' => [
            'label' => 'Atjaunot',
            'modal' => [
                'heading' => 'Atjaunot :label',
                'actions' => [
                    'restore' => [
                        'label' => 'Atjaunot',
                    ],
                ],
            ],
            'notifications' => [
                'restored' => [
                    'title' => 'Atjaunots',
                ],
            ],
        ],
        'multiple' => [
            'label' => 'Atjaunot izvēlētos',
            'modal' => [
                'heading' => 'Atjaunot izvēlētos ierakstus',
                'actions' => [
                    'restore' => [
                        'label' => 'Atjaunot',
                    ],
                ],
            ],
            'notifications' => [
                'restored' => [
                    'title' => 'Ieraksti atjaunoti',
                ],
                'restored_none' => [
                    'title' => 'Nekas nav atjaunots',
                ],
                'restored_partial' => [
                    'title' => 'Daži ieraksti atjaunoti',
                ],
            ],
        ],
    ],

    'replicate' => [
        'single' => [
            'label' => 'Dublēt',
            'modal' => [
                'heading' => 'Dublēt :label',
                'actions' => [
                    'replicate' => [
                        'label' => 'Dublēt',
                    ],
                ],
            ],
            'notifications' => [
                'replicated' => [
                    'title' => 'Dublēts',
                ],
            ],
        ],
    ],

    'view' => [
        'single' => [
            'label' => 'Skatīt',
            'modal' => [
                'heading' => 'Skatīt :label',
                'actions' => [
                    'close' => [
                        'label' => 'Aizvērt',
                    ],
                ],
            ],
        ],
    ],

    'group' => [
        'trigger' => [
            'label' => 'Darbības',
        ],
    ],

    'modal' => [
        'actions' => [
            'cancel' => [
                'label' => 'Atcelt',
            ],
            'confirm' => [
                'label' => 'Apstiprināt',
            ],
            'submit' => [
                'label' => 'Iesniegt',
            ],
        ],
        'confirmation' => 'Vai tiešām vēlaties turpināt?',
    ],

    'notifications' => [
        'throttled' => [
            'title' => 'Lūdzu, uzgaidiet',
            'body' => 'Pārāk daudz mēģinājumu. Mēģiniet vēlreiz pēc brīža.',
        ],
    ],

];
