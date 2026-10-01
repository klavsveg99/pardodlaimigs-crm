<?php

/**
 * CRM uzlabojumu konfigurācija: "Laimīgo" klientu dzimšanas dienu
 * atgādinājumi un juristam nosūtāmo dokumentu lauki.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Dzimšanas dienu atgādinājumi
    |--------------------------------------------------------------------------
    |
    | Cik dienas pirms dzimšanas dienas sāk rādīt atgādinājumu (ieskaitot
    | pašu dzimšanas dienu). Attiecas tikai uz "Laimīgajiem" klientiem.
    |
    */
    'birthdays' => [
        'days_before' => (int) env('CRM_BIRTHDAY_DAYS_BEFORE', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Jurista dokumenti
    |--------------------------------------------------------------------------
    */
    'lawyer' => [

        /*
         | Manuāli aizpildāmie lauki (glabājas crm_properties.legal_data).
         | type: text | textarea | date | money | select | repeater
         */
        'fields' => [
            'signing_date' => ['label' => 'Līguma slēgšanas datums', 'type' => 'date'],
            'signing_place' => ['label' => 'Līguma slēgšanas vieta (pilsēta)', 'type' => 'text'],
            'signing_format' => [
                'label' => 'Līguma slēgšanas veids',
                'type' => 'select',
                'options' => ['Klātienē' => 'Klātienē', 'Attālināti' => 'Attālināti'],
            ],
            'hand_money_contract' => [
                'label' => 'Rokas naudas līgums noslēgts',
                'type' => 'select',
                'options' => ['Jā' => 'Jā', 'Nē' => 'Nē'],
            ],
            'hand_money_amount' => ['label' => 'Rokas naudas summa (€)', 'type' => 'money'],
            'hand_money_date' => ['label' => 'Rokas naudas iemaksas datums', 'type' => 'date'],
            'payment_total' => ['label' => 'Pilna pirkuma maksa (€)', 'type' => 'money'],
            'financing_type' => [
                'label' => 'Finansējuma veids',
                'type' => 'select',
                'options' => [
                    'Pircēja pašu līdzekļi' => 'Pircēja pašu līdzekļi',
                    'Bankas kredīts' => 'Bankas kredīts',
                ],
            ],
            'bank_name' => ['label' => 'Bankas nosaukums', 'type' => 'text'],
            'payment_down' => ['label' => 'Pircēja pašu pirmā iemaksa (€)', 'type' => 'money'],
            'altum_guarantee' => [
                'label' => 'Tiek izmantota ALTUM garantija',
                'type' => 'select',
                'options' => ['Jā' => 'Jā', 'Nē' => 'Nē'],
            ],
            'payment_plan' => [
                'label' => 'Maksājumu grafiks',
                'type' => 'repeater',
                'columns' => [
                    'amount' => 'Summa (€)',
                    'count' => 'Maksājumu skaits',
                    'note' => 'Piezīme',
                ],
            ],
            'payment_plan_first_day' => ['label' => 'Maksājumi veicami līdz (mēneša diena)', 'type' => 'text'],
            'payment_remaining' => ['label' => 'Atlikusī summa/izpirkums', 'type' => 'textarea'],
            'release_date' => ['label' => 'Īpašuma atbrīvošanas datums', 'type' => 'date'],
            'seller_spouse' => [
                'label' => 'Pārdevējam ir laulātais',
                'type' => 'select',
                'options' => ['Nav' => 'Nav', 'Ir' => 'Ir'],
            ],
            'spouse_name' => ['label' => 'Laulātā vārds, uzvārds', 'type' => 'text'],
            'notes' => ['label' => 'Piezīmes / speciālie nosacījumi', 'type' => 'textarea'],
        ],

        /*
         | Dokumentu veidi. required = manuālo lauku atslēgas, bez kurām
         | nosūtīšana nav atļauta. Papildu veidus var pievienot šeit.
         | Lauku redzamību pēc veida nosaka LawyerDocumentAction.
         */
        'documents' => [
            'rokas_nauda' => [
                'label' => 'Rokas naudas līgums',
                'required' => ['signing_date', 'signing_place', 'hand_money_amount', 'hand_money_date'],
            ],
            'pirkums' => [
                'label' => 'Pirkuma līgums',
                'required' => ['signing_date', 'signing_place', 'payment_total', 'financing_type'],
            ],
            'pirkums_nomaksa' => [
                'label' => 'Pirkuma līgums ar nomaksu',
                'required' => ['signing_date', 'signing_place', 'payment_total', 'financing_type', 'payment_plan'],
            ],
        ],
    ],

];
