<?php

return [

    'components' => [

        'select' => [
            'placeholder' => 'Izvēlieties opciju',
            'no_options_message' => 'Nav pieejamu opciju',
            'no_search_results_message' => 'Nekas netika atrasts',
            'search_prompt' => 'Ierakstiet, lai meklētu...',
            'searching_message' => 'Meklēšana...',
            'loading_message' => 'Ielāde...',
            'max_items_message' => 'Var izvēlēties ne vairāk kā :count',
            'boolean' => [
                'true' => 'Jā',
                'false' => 'Nē',
            ],
            'actions' => [
                'create_option' => [
                    'label' => 'Izveidot opciju',
                    'modal' => [
                        'heading' => 'Izveidot opciju',
                        'actions' => [
                            'create' => [
                                'label' => 'Izveidot',
                            ],
                            'create_another' => [
                                'label' => 'Izveidot un vēl vienu',
                            ],
                        ],
                    ],
                ],
                'edit_option' => [
                    'label' => 'Rediģēt opciju',
                    'modal' => [
                        'heading' => 'Rediģēt opciju',
                        'actions' => [
                            'save' => [
                                'label' => 'Saglabāt',
                            ],
                        ],
                    ],
                ],
            ],
        ],

        'tags_input' => [
            'placeholder' => 'Ierakstiet un nospiediet Enter',
            'tag_added' => 'Birka pievienota',
            'tag_removed' => 'Birka noņemta',
            'actions' => [
                'delete' => [
                    'label' => 'Dzēst birku',
                ],
            ],
        ],

        'checkbox_list' => [
            'actions' => [
                'select_all' => [
                    'label' => 'Izvēlēties visu',
                ],
                'deselect_all' => [
                    'label' => 'Noņemt atlasi',
                ],
            ],
        ],

        'text_input' => [
            'actions' => [
                'copy' => [
                    'label' => 'Kopēt',
                    'message' => 'Nokopēts starpliktuvē',
                ],
                'hide_password' => [
                    'label' => 'Paslēpt paroli',
                ],
                'show_password' => [
                    'label' => 'Rādīt paroli',
                ],
            ],
        ],

        'toggle' => [
            'boolean' => [
                'true' => 'Ieslēgts',
                'false' => 'Izslēgts',
            ],
        ],

        'toggle_buttons' => [
            'boolean' => [
                'true' => 'Jā',
                'false' => 'Nē',
            ],
        ],

        'radio' => [
            'boolean' => [
                'true' => 'Jā',
                'false' => 'Nē',
            ],
        ],

        'file_upload' => [
            'actions' => [
                'download' => [
                    'label' => 'Lejupielādēt',
                ],
                'open' => [
                    'label' => 'Atvērt',
                ],
            ],
            'editor' => [
                'label' => 'Rediģēt attēlu',
                'aspect_ratios' => [
                    'label' => 'Izmēri',
                    'no_fixed' => 'Brīvs',
                ],
                'fields' => [
                    'x_position' => [
                        'label' => 'X',
                        'unit' => 'px',
                    ],
                    'y_position' => [
                        'label' => 'Y',
                        'unit' => 'px',
                    ],
                    'width' => [
                        'label' => 'Platums',
                        'unit' => 'px',
                    ],
                    'height' => [
                        'label' => 'Augstums',
                        'unit' => 'px',
                    ],
                    'rotation' => [
                        'label' => 'Pagriešana',
                        'unit' => '°',
                    ],
                ],
                'actions' => [
                    'save' => [
                        'label' => 'Saglabāt',
                    ],
                    'cancel' => [
                        'label' => 'Atcelt',
                    ],
                    'reset' => [
                        'label' => 'Atiestatīt',
                    ],
                    'zoom_in' => [
                        'label' => 'Tuvināt',
                    ],
                    'zoom_out' => [
                        'label' => 'Tālināt',
                    ],
                    'zoom_100' => [
                        'label' => '100%',
                    ],
                    'rotate_left' => [
                        'label' => 'Pagriezt pa kreisi',
                    ],
                    'rotate_right' => [
                        'label' => 'Pagriezt pa labi',
                    ],
                    'flip_horizontal' => [
                        'label' => 'Apmest horizontāli',
                    ],
                    'flip_vertical' => [
                        'label' => 'Apmest vertikāli',
                    ],
                    'move_up' => [
                        'label' => 'Augšup',
                    ],
                    'move_down' => [
                        'label' => 'Lejup',
                    ],
                    'move_left' => [
                        'label' => 'Pa kreisi',
                    ],
                    'move_right' => [
                        'label' => 'Pa labi',
                    ],
                    'drag_move' => [
                        'label' => 'Pārvietot',
                    ],
                    'drag_crop' => [
                        'label' => 'Apgriezt',
                    ],
                    'set_aspect_ratio' => [
                        'label' => 'Iestatīt izmēru',
                    ],
                ],
                'svg' => [
                    'messages' => [
                        'confirmation' => 'Vai tiešām vēlaties ievietot SVG bez apstrādes?',
                        'disabled' => 'SVG apstrāde ir atspējota.',
                    ],
                ],
            ],
        ],

        'key_value' => [
            'actions' => [
                'add' => [
                    'label' => 'Pievienot rindu',
                ],
                'delete' => [
                    'label' => 'Dzēst',
                ],
                'reorder' => [
                    'label' => 'Kārtot',
                ],
            ],
            'columns' => [
                'actions' => [
                    'label' => 'Darbības',
                ],
                'reorder' => [
                    'label' => 'Kārtot',
                ],
            ],
            'fields' => [
                'key' => [
                    'label' => 'Atslēga',
                ],
                'value' => [
                    'label' => 'Vērtība',
                ],
            ],
        ],

        'date_time_picker' => [
            'hour_input' => [
                'label' => 'Stundas',
            ],
            'minute_input' => [
                'label' => 'Minūtes',
            ],
            'second_input' => [
                'label' => 'Sekundes',
            ],
            'month_select' => [
                'label' => 'Mēnesis',
            ],
            'year_input' => [
                'label' => 'Gads',
            ],
        ],

        'repeater' => [
            'index' => 'Rinda :index',
            'actions' => [
                'add' => [
                    'label' => 'Pievienot',
                ],
                'add_between' => [
                    'label' => 'Ievietot šeit',
                ],
                'clone' => [
                    'label' => 'Kopēt',
                ],
                'delete' => [
                    'label' => 'Dzēst',
                ],
                'collapse' => [
                    'label' => 'Sakļaut',
                ],
                'collapse_all' => [
                    'label' => 'Sakļaut visu',
                ],
                'expand' => [
                    'label' => 'Izvērst',
                ],
                'expand_all' => [
                    'label' => 'Izvērst visu',
                ],
                'move_up' => [
                    'label' => 'Augšup',
                ],
                'move_down' => [
                    'label' => 'Lejup',
                ],
                'reorder' => [
                    'label' => 'Kārtot',
                ],
            ],
            'columns' => [
                'actions' => [
                    'label' => 'Darbības',
                ],
                'reorder' => [
                    'label' => 'Kārtot',
                ],
            ],
        ],

        'builder' => [
            'actions' => [
                'add' => [
                    'label' => 'Pievienot bloku',
                    'modal' => [
                        'heading' => 'Pievienot bloku',
                        'actions' => [
                            'add' => [
                                'label' => 'Pievienot',
                            ],
                        ],
                    ],
                ],
                'add_between' => [
                    'label' => 'Ievietot šeit',
                    'modal' => [
                        'heading' => 'Pievienot bloku',
                        'actions' => [
                            'add' => [
                                'label' => 'Pievienot',
                            ],
                        ],
                    ],
                ],
                'clone' => [
                    'label' => 'Kopēt',
                ],
                'delete' => [
                    'label' => 'Dzēst',
                ],
                'edit' => [
                    'label' => 'Rediģēt',
                    'modal' => [
                        'heading' => 'Rediģēt bloku',
                        'actions' => [
                            'save' => [
                                'label' => 'Saglabāt',
                            ],
                        ],
                    ],
                ],
                'collapse' => [
                    'label' => 'Sakļaut',
                ],
                'collapse_all' => [
                    'label' => 'Sakļaut visu',
                ],
                'expand' => [
                    'label' => 'Izvērst',
                ],
                'expand_all' => [
                    'label' => 'Izvērst visu',
                ],
                'move_up' => [
                    'label' => 'Augšup',
                ],
                'move_down' => [
                    'label' => 'Lejup',
                ],
                'reorder' => [
                    'label' => 'Kārtot',
                ],
            ],
            'block-picker' => [
                'label' => 'Bloki',
            ],
        ],

        'rich_editor' => [
            'toolbar' => [
                'label' => 'Rīkjosla',
            ],
            'no_merge_tag_search_results_message' => 'Nav atrastu rezultātu',
            'uploading_file_message' => 'Augšupielādē failu...',
            'file_attachments_accepted_file_types_message' => 'Atļautie failu tipi: :types',
            'file_attachments_max_size_message' => 'Maksimālais faila izmērs: :max',
            'tools' => [
                'align_start' => 'Līdzināt pa kreisi',
                'align_center' => 'Centrēt',
                'align_end' => 'Līdzināt pa labi',
                'align_justify' => 'Izlīdzināt',
                'bold' => 'Treknraksts',
                'italic' => 'Slīpraksts',
                'underline' => 'Pasvītrots',
                'strike' => 'Pārsvītrots',
                'bullet_list' => 'Nenumurēts saraksts',
                'ordered_list' => 'Numurēts saraksts',
                'h1' => 'Virsraksts 1',
                'h2' => 'Virsraksts 2',
                'h3' => 'Virsraksts 3',
                'h4' => 'Virsraksts 4',
                'h5' => 'Virsraksts 5',
                'h6' => 'Virsraksts 6',
                'paragraph' => 'Rindkopa',
                'lead' => 'Ievads',
                'small' => 'Mazs teksts',
                'blockquote' => 'Citāts',
                'code' => 'Kods',
                'code_block' => 'Koda bloks',
                'link' => 'Saite',
                'highlight' => 'Izcēlums',
                'horizontal_rule' => 'Horizontālā līnija',
                'clear_formatting' => 'Notīrīt formatējumu',
                'undo' => 'Atsaukt',
                'redo' => 'Atkārtot',
                'superscript' => 'Augšraksts',
                'subscript' => 'Apakšraksts',
                'table' => 'Tabula',
                'table_add_column_before' => 'Pievienot kolonnu pirms',
                'table_add_column_after' => 'Pievienot kolonnu pēc',
                'table_add_row_before' => 'Pievienot rindu pirms',
                'table_add_row_after' => 'Pievienot rindu pēc',
                'table_delete_column' => 'Dzēst kolonnu',
                'table_delete_row' => 'Dzēst rindu',
                'table_delete' => 'Dzēst tabulu',
                'table_merge_cells' => 'Apvienot šūnas',
                'table_split_cell' => 'Sadalīt šūnu',
                'table_toggle_header_row' => 'Galvenes rinda',
                'table_toggle_header_cell' => 'Galvenes šūna',
                'text_color' => 'Teksta krāsa',
                'attach_files' => 'Pievienot failus',
                'grid' => 'Režģis',
                'grid_delete' => 'Dzēst režģi',
                'details' => 'Sīkāka informācija',
                'custom_blocks' => 'Pielāgoti bloki',
                'merge_tags' => 'Apvienošanas tagi',
            ],
            'actions' => [
                'link' => [
                    'label' => 'Saite',
                    'modal' => [
                        'heading' => 'Rediģēt saiti',
                        'form' => [
                            'url' => [
                                'label' => 'Adrese',
                            ],
                            'should_open_in_new_tab' => [
                                'label' => 'Atvērt jaunā cilnē',
                            ],
                        ],
                    ],
                ],
                'text_color' => [
                    'label' => 'Teksta krāsa',
                    'modal' => [
                        'heading' => 'Teksta krāsa',
                        'form' => [
                            'color' => [
                                'label' => 'Krāsa',
                            ],
                            'custom_color' => [
                                'label' => 'Pielāgota krāsa',
                            ],
                        ],
                    ],
                ],
                'grid' => [
                    'label' => 'Režģis',
                    'modal' => [
                        'heading' => 'Režģa iestatījumi',
                        'form' => [
                            'columns' => [
                                'label' => 'Kolonnu skaits',
                            ],
                            'start_span' => [
                                'label' => 'Sākuma izvēršana',
                            ],
                            'end_span' => [
                                'label' => 'Beigu izvēršana',
                            ],
                            'is_asymmetric' => [
                                'label' => 'Asimetrisks',
                            ],
                            'preset' => [
                                'label' => 'Šablons',
                                'placeholder' => 'Izvēlieties šablonu',
                            ],
                            'from_breakpoint' => [
                                'label' => 'No kontrolpunkta',
                                'options' => [
                                    'default' => 'Noklusējums',
                                    'sm' => 'SM',
                                    'md' => 'MD',
                                    'lg' => 'LG',
                                    'xl' => 'XL',
                                    '2xl' => '2XL',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'mentions' => [
                'search_prompt' => 'Ierakstiet, lai meklētu...',
                'searching_message' => 'Meklēšana...',
                'no_search_results_message' => 'Nav atrastu rezultātu',
                'no_options_message' => 'Nav opciju',
            ],
        ],

        'markdown_editor' => [
            'file_attachments_accepted_file_types_message' => 'Atļautie failu tipi: :types',
            'file_attachments_max_size_message' => 'Maksimālais faila izmērs: :max',
        ],

        'color_picker' => [
            'panel_label' => 'Krāsa',
        ],

    ],

    'validation' => [
        'distinct' => [
            'must_be_selected' => 'Jābūt izvēlētam vienam ierakstam.',
            'only_one_must_be_selected' => 'Jābūt izvēlētam tikai vienam ierakstam.',
        ],
        'tampered_file_path' => 'Faila ceļš ir bojāts.',
    ],

];
