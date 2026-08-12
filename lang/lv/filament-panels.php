<?php

return [

    'auth' => [

        'pages' => [

            'login' => [
                'heading' => 'Pieslēgties',
                'form' => [
                    'email' => [
                        'label' => 'E-pasts',
                        'placeholder' => 'epasts@piemeram.lv',
                    ],
                    'password' => [
                        'label' => 'Parole',
                        'placeholder' => 'Ievadiet paroli',
                    ],
                    'actions' => [
                        'authenticate' => [
                            'label' => 'Pieslēgties',
                            'extra' => 'Pieslēdzoties, jūs piekrītat mūsu noteikumiem.',
                        ],
                    ],
                ],
            ],

            'request-password-reset' => [
                'heading' => 'Aizmirsta parole',
                'form' => [
                    'email' => [
                        'label' => 'E-pasts',
                        'placeholder' => 'epasts@piemeram.lv',
                    ],
                    'actions' => [
                        'request' => [
                            'label' => 'Nosūtīt atjaunošanas saiti',
                        ],
                    ],
                ],
            ],

            'reset-password' => [
                'heading' => 'Atiestatīt paroli',
                'form' => [
                    'password' => [
                        'label' => 'Jauna parole',
                        'placeholder' => 'Ievadiet jaunu paroli',
                    ],
                    'passwordConfirmation' => [
                        'label' => 'Apstipriniet paroli',
                        'placeholder' => 'Ievadiet paroli vēlreiz',
                    ],
                    'actions' => [
                        'reset' => [
                            'label' => 'Atiestatīt paroli',
                        ],
                    ],
                ],
            ],

            'edit-profile' => [
                'heading' => 'Mans profils',
                'form' => [
                    'name' => [
                        'label' => 'Vārds',
                    ],
                    'email' => [
                        'label' => 'E-pasts',
                    ],
                    'password' => [
                        'label' => 'Jauna parole',
                        'placeholder' => 'Atstājiet tukšu, ja nemaināt',
                    ],
                    'passwordConfirmation' => [
                        'label' => 'Apstipriniet paroli',
                    ],
                    'actions' => [
                        'save' => [
                            'label' => 'Saglabāt izmaiņas',
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

    ],

    'layout' => [
        'actions' => [
            'logout' => [
                'label' => 'Izrakstīties',
            ],
            'billing' => [
                'label' => 'Abonements',
            ],
        ],
        'direction' => 'ltr',
    ],

    'livewire' => [
        'global-search' => 'Meklēt',
        'sidebar' => 'Sānjosla',
        'topbar' => 'Augšjosla',
        'simple-user-menu' => 'Lietotāja izvēlne',
    ],

    'error-notifications' => [
        'title' => 'Kļūda',
        'body' => 'Kaut kas nogāja greizi.',
    ],

    'pages' => [
        'page' => [
            'title' => ':title',
        ],
        'simple' => [],
    ],

    'resources' => [
        'relation-manager' => [
            'label' => ':label',
        ],
    ],

    'widgets' => [
        'account-widget' => [
            'actions' => [
                'logout' => [
                    'label' => 'Izrakstīties',
                ],
            ],
        ],
        'filament-info-widget' => [
            'Made with' => 'Izveidots ar',
        ],
    ],

    'components' => [
        'layout' => [
            'base' => [],
            'index' => [],
            'simple' => [],
        ],
        'page' => [
            'index' => [],
        ],
        'sidebar' => [
            'database-notifications-trigger' => [],
        ],
        'topbar' => [
            'database-notifications-trigger' => [],
        ],
    ],

];
