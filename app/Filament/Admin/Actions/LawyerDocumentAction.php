<?php

declare(strict_types=1);

namespace App\Filament\Admin\Actions;

use App\Filament\Forms\Components\PersonasKodsInput;
use App\Filament\Forms\Components\PhoneInput;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Rules\Phone;
use App\Services\Lawyer\LawyerDocumentService;
use App\Services\Mail\EmailTooLargeException;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Alignment;

/**
 * "Jurista dokuments" darbība: izvēlas dokumenta veidu un juristu, ļauj
 * aizpildīt trūkstošos datus, pārbauda obligātos laukus un nosūta
 * strukturētu e-pastu.
 *
 * Pieejama jebkurā statusā (izņemot "Dzēsts") īpašumam ar piesaistītu
 * pārdevēju — līgums jāsagatavo pirms pārdošanas. Viena un tā pati darbība
 * tiek izmantota gan īpašuma skatā, gan īpašumu saraksta rindā.
 *
 * Lauku redzamība ir dinamiska: rokas naudas dati pieder rokas naudas
 * līgumam (vai pirkumam ar atzīmētu rokas naudas līgumu), maksājumu grafiks
 * tikai pirkumam ar nomaksu, bet bankas lauki tikai bankas kredītam.
 */
final class LawyerDocumentAction
{
    public static function make(): Actions\Action
    {
        return Actions\Action::make('lawyer_document')
            ->label('Jurista dokuments')
            ->icon('heroicon-o-scale')
            ->color('gray')
            ->visible(fn (?CrmProperty $record): bool => $record !== null
                && $record->status !== 'deleted'
                && ! auth()->user()?->isPhoto())
            ->modalHeading('Nosūtīt juristam')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Nosūtīt')
            ->modalCancelAction(false)
            ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard
                ->hiddenHeader()
                ->nextAction(fn (Actions\Action $action): Actions\Action => $action->label('Tālāk'))
                ->previousAction(fn (Actions\Action $action): Actions\Action => $action->label('Atpakaļ')))
            ->steps(fn (CrmProperty $record): array => self::steps($record))
            ->action(fn (array $data, CrmProperty $record) => self::sendFromForm($record, $data));
    }

    /**
     * Divi soļi: datu aizpilde, tad e-pasts. Soļu navigācija augšā tiek
     * paslēpta; pārejas notiek ar Filament kājienes pogām (Atpakaļ/Tālāk),
     * pēdējā solī — "Nosūtīt".
     *
     * @return array<int, Step>
     */
    private static function steps(CrmProperty $property): array
    {
        return [
            Step::make('Dokumenta dati')
                ->schema(self::formFields($property))
                ->columns(1)
                ->afterValidation(function (Get $get, Set $set): void {
                    self::prepareEmail($get, $set, $property);
                }),

            Step::make('E-pasts')
                ->schema(self::emailFields($property)),
        ];
    }

    /**
     * 2. solis: e-pasts juristam. Lauki ir nativie Filament lauki, bet
     * sakārtoti tāpat kā klienta pielikumu e-pasta modālī.
     *
     * @return array<int, mixed>
     */
    private static function emailFields(CrmProperty $property): array
    {
        $files = self::emailFiles($property);

        $preview = View::make('filament.admin.partials.lawyer-email-preview')
            ->viewData(function (Get $get) use ($property): array {
                return [
                    'bodyHtml' => self::previewBody($get, $property),
                ];
            });

        return [
            Section::make()->columns(1)->schema([
                Forms\Components\TextInput::make('email_to')
                    ->label('Saņēmējs')
                    ->email()
                    ->required()
                    ->default(fn (Get $get): string => (string) (app(LawyerDocumentService::class)->jurist($get('jurist_id'))?->email ?? '')),
                Forms\Components\TextInput::make('email_subject')
                    ->label('Temats')
                    ->required()
                    ->default(fn (Get $get) => self::previewSubject($get, $property)),
                Forms\Components\CheckboxList::make('email_files')
                    ->label('Faili')
                    ->options(collect($files)->mapWithKeys(fn (array $f): array => [$f['id'] => $f['name'].($f['size'] ? ' ('.self::sizeLabel($f['size']).')' : '')])->all())
                    ->helperText('Atzīmē failus, kurus pievienot e-pastam.')
                    ->columnSpanFull(),
            ]),

            Section::make('E-pasta priekšskatījums')->columns(1)->schema([$preview]),

            Section::make()->columns(1)->schema([
                Forms\Components\Textarea::make('extra_info')
                    ->label('Papildus informācija')
                    ->rows(3)
                    ->helperText('Ja aizpildīts, tiek pievienots e-pasta beigās.')
                    ->columnSpanFull(),
            ]),
        ];
    }

    /** E-pasta pamatteksts no formas datiem (2. soļa priekšskatījumam). */
    private static function previewBody(Get $get, CrmProperty $property): string
    {
        $type = (string) $get('document_type');

        return app(LawyerDocumentService::class)->preview([
            'document_type' => $type,
            'jurist_id' => $get('jurist_id'),
            'legal' => self::sanitizeLegal($type, $get('legal') ?? []),
            'seller' => $get('seller') ?? [],
            'buyer' => $get('buyer') ?? [],
            'property' => $get('property') ?? [],
            'attachment_ids' => [],
        ], $property)['body'];
    }

    private static function previewSubject(Get $get, CrmProperty $property): string
    {
        $type = (string) $get('document_type');

        return app(LawyerDocumentService::class)->preview([
            'document_type' => $type,
            'jurist_id' => $get('jurist_id'),
            'legal' => self::sanitizeLegal($type, $get('legal') ?? []),
            'seller' => $get('seller') ?? [],
            'buyer' => $get('buyer') ?? [],
            'property' => $get('property') ?? [],
            'attachment_ids' => [],
        ], $property)['subject'];
    }

    private static function sizeLabel(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024).' KB';
        }

        return rtrim(rtrim(number_format($bytes / (1024 * 1024), 1, '.', ''), '0'), '.').' MB';
    }

    /**
     * 2. soļa nosūtīšana: ņem modāļa laukus (saņēmējs, temats, faili,
     * Papildus informācija) un sūta caur to pašu EmailSender plūsmu kā
     * klienta pielikumu e-pasts.
     *
     * @param  array<string, mixed>  $data
     */
    private static function sendFromForm(CrmProperty $property, array $data): void
    {
        $service = app(LawyerDocumentService::class);
        $jurist = $service->jurist($data['jurist_id'] ?? null);

        if (! $jurist) {
            Notification::make()
                ->title('Jurists nav atrasts')
                ->danger()
                ->send();

            return;
        }

        $files = is_array($data['email_files'] ?? null) ? $data['email_files'] : [];

        if ($files === []) {
            Notification::make()
                ->title('Izvēlieties vismaz vienu failu')
                ->danger()
                ->send();

            return;
        }

        $extra = trim((string) ($data['extra_info'] ?? ''));
        $body = self::previewBodyFromData($data, $property);
        if ($extra !== '') {
            $body .= '<p>'.e($extra).'</p>';
        }

        try {
            $request = $service->send(
                $property->refresh(),
                $jurist,
                (string) ($data['document_type'] ?? ''),
                is_array($property->legal_data) ? $property->legal_data : [],
                auth()->user(),
                $files,
                (string) ($data['email_to'] ?? ''),
                (string) ($data['email_subject'] ?? ''),
                $body,
            );
        } catch (EmailTooLargeException $e) {
            Notification::make()->title($e->userMessage())->danger()->send();

            return;
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Nosūtīšana neizdevās')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Nosūtīts juristam: '.$jurist->name)
            ->body($request->subject)
            ->success()
            ->send();
    }

    /** @param  array<string, mixed>  $data */
    private static function previewBodyFromData(array $data, CrmProperty $property): string
    {
        $type = (string) ($data['document_type'] ?? '');

        return app(LawyerDocumentService::class)->preview([
            'document_type' => $type,
            'jurist_id' => $data['jurist_id'] ?? null,
            'legal' => is_array($data['legal'] ?? null) ? $data['legal'] : [],
            'seller' => $data['seller'] ?? [],
            'buyer' => $data['buyer'] ?? [],
            'property' => $data['property'] ?? [],
            'attachment_ids' => [],
        ], $property)['body'];
    }

    /** Sagatavo (un saglabā) 1. soļa datus pirms pārejas uz e-pasta soli. */
    private static function prepareEmail(Get $get, Set $set, CrmProperty $property): void
    {
        $type = (string) $get('document_type');
        $legal = is_array($get('legal')) ? $get('legal') : [];

        self::applyClientUpdates(self::sellerOf($property), is_array($get('seller')) ? $get('seller') : []);
        self::applyClientUpdates(self::buyerOf($property), is_array($get('buyer')) ? $get('buyer') : []);
        self::applyPropertyUpdates($property, is_array($get('property')) ? $get('property') : []);

        foreach (['seller', 'buyer'] as $role) {
            $client = $role === 'seller' ? self::sellerOf($property) : self::buyerOf($property);
            $form = is_array($get($role)) ? $get($role) : [];

            if ($client === null) {
                $block = self::normalizedFields($form, ['person_type', 'name', 'personas_kods', 'address', 'bank_account', 'email', 'phone']);
                if ($block !== []) {
                    $legal[$role] = $block;
                }
            }
        }

        $legalData = self::sanitizeLegal($type, array_merge(
            is_array($property->legal_data) ? $property->legal_data : [],
            $legal,
        ));

        $property->legal_data = $legalData;
        $property->save();

        $set('legal', $legalData);
    }

    /** Esošie īpašuma un piesaistīto klientu faili e-pasta modālim. */
    private static function emailFiles(CrmProperty $property): array
    {
        $property->loadMissing('attachments', 'clients.attachments');

        $roles = ['seller' => 'Pārdevējs', 'buyer' => 'Pircējs'];

        $files = $property->attachments
            ->where('collection', 'documents')
            ->map(fn ($a): array => [
                'id' => (int) $a->id,
                'name' => (string) $a->original_name,
                'size' => (int) $a->size,
            ]);

        foreach ($property->clients as $client) {
            $role = $roles[$client->pivot->relation] ?? 'Klients';

            $files = $files->concat($client->attachments->map(fn ($a): array => [
                'id' => (int) $a->id,
                'name' => (string) $a->original_name.' · '.$role,
                'size' => (int) $a->size,
            ]));
        }

        return $files->values()->all();
    }

    private static function propertyParam(CrmProperty $property): string
    {
        return (string) ($property->slug ?: $property->getKey());
    }

    /** @return array<int, mixed> */
    private static function formFields(CrmProperty $property): array
    {
        $service = app(LawyerDocumentService::class);
        $documents = $service->documentTypes();
        $seller = self::sellerOf($property);
        $buyer = self::buyerOf($property);
        $legal = is_array($property->legal_data) ? $property->legal_data : [];

        // Ja pārdevējs/pircējs nav piesaistīts kā klients, datus ņemam no
        // iepriekš aizpildītā manuālā bloka.
        $sellerDefaults = $seller
            ? [
                'person_type' => $seller->person_type ?? 'fiziska',
                'name' => $seller->name,
                'personas_kods' => $seller->personas_kods,
                'address' => $seller->address,
                'bank_account' => $seller->bank_account,
                'email' => $seller->email,
                'phone' => $seller->phone,
            ]
            : (is_array($legal['seller'] ?? null) ? $legal['seller'] : []);

        $buyerDefaults = $buyer
            ? [
                'person_type' => $buyer->person_type ?? 'fiziska',
                'name' => $buyer->name,
                'personas_kods' => $buyer->personas_kods,
                'address' => $buyer->address,
                'bank_account' => $buyer->bank_account,
                'email' => $buyer->email,
                'phone' => $buyer->phone,
            ]
            : (is_array($legal['buyer'] ?? null) ? $legal['buyer'] : []);

        // Dokumenta veids un finansējums nosaka, kuri lauki ir redzami.
        $isPurchase = fn (Get $get): bool => in_array($get('document_type'), ['pirkums', 'pirkums_nomaksa'], true);
        $isInstallment = fn (Get $get): bool => $get('document_type') === 'pirkums_nomaksa';
        $isHandMoneyDoc = fn (Get $get): bool => $get('document_type') === 'rokas_nauda';
        $bankCredit = fn (Get $get): bool => $isPurchase($get) && $get('legal.financing_type') === 'Bankas kredīts';
        $handMoney = fn (Get $get): bool => $isHandMoneyDoc($get)
            || ($isPurchase($get) && $get('legal.hand_money_contract') === 'Jā');

        $visibleFor = [
            'hand_money_contract' => $isPurchase,
            'hand_money_amount' => $handMoney,
            'hand_money_date' => $handMoney,
            'payment_total' => $isPurchase,
            'financing_type' => $isPurchase,
            'bank_name' => $bankCredit,
            'payment_down' => $bankCredit,
            'payment_plan' => $isInstallment,
            'payment_plan_first_day' => $isInstallment,
            'payment_remaining' => $isInstallment,
            'release_date' => $isPurchase,
        ];

        // Noklusējumi, lai izvairītos no atkārtotas tās pašas informācijas
        // ievades (pirkuma maksa no īpašuma cenas, vietai — īpašuma pilsēta).
        $defaults = [
            'signing_place' => $property->city,
            'hand_money_contract' => 'Nē',
            'payment_total' => (float) $property->final_price_eur > 0
                ? $property->final_price_eur
                : ((float) $property->price_eur > 0 ? $property->price_eur : null),
        ];

        $manual = [];

        foreach ($service->fields() as $key => $definition) {
            $component = match ($definition['type']) {
                'textarea' => Forms\Components\Textarea::make("legal.$key")->rows(2),
                'date' => Forms\Components\DatePicker::make("legal.$key")->native(false)->displayFormat('d.m.Y'),
                'money' => Forms\Components\TextInput::make("legal.$key")->numeric()->prefix('€'),
                'select' => Forms\Components\Select::make("legal.$key")->options($definition['options'] ?? []),
                'repeater' => Forms\Components\Repeater::make("legal.$key")
                    ->schema(collect($definition['columns'] ?? [])
                        ->map(fn (string $label, string $column) => Forms\Components\TextInput::make($column)->label($label))
                        ->values()
                        ->all())
                    ->columns(max(1, count($definition['columns'] ?? [])))
                    ->addActionLabel('Pievienot rindu')
                    ->addActionAlignment(Alignment::Start)
                    ->defaultItems(0),
                default => Forms\Components\TextInput::make("legal.$key"),
            };

            $component
                ->label($definition['label'])
                ->default($legal[$key] ?? $defaults[$key] ?? null)
                // Obligāti: dokumenta veida lauki plus bankas lauki, ja
                // izvēlēts bankas kredīts.
                ->required(function (Get $get) use ($key, $documents): bool {
                    if (in_array($key, $documents[$get('document_type')]['required'] ?? [], true)) {
                        return true;
                    }

                    return in_array($key, ['bank_name', 'payment_down'], true)
                        && $get('legal.financing_type') === 'Bankas kredīts';
                });

            if (isset($visibleFor[$key])) {
                $component->visible($visibleFor[$key]);
            }

            // Lauki, kas maina citu lauku redzamību.
            if (in_array($key, ['hand_money_contract', 'financing_type'], true)) {
                $component->live();
            }

            // "Maksājumu grafiks" un "Piezīmes" aizņem visu rindu; pārējie
            // lauki iet pa pāriem, lai 2. kolonna nepaliktu tukša.
            if (in_array($key, ['payment_plan', 'notes'], true)) {
                $component->columnSpanFull();
            }

            // Laulātā lauks redzams tikai tad, kad laulātais ir atzīmēts;
            // paslēpts, tas pārņem visu rindu, lai 2. kolonna nepaliktu tukša.
            if ($key === 'seller_spouse') {
                $component
                    ->live()
                    ->columnSpan(fn (Get $get) => $get('legal.seller_spouse') === 'Ir' ? 1 : 'full');
            }

            if ($key === 'spouse_name') {
                $component->visible(fn (Get $get): bool => $get('legal.seller_spouse') === 'Ir');
            }

            $manual[] = $component;
        }

        // Vienas un tās pašas datu kolonnas, atšķiras tikai nosaukumi atkarībā
        // no izvēlētā personas veida (fiziska vai juridiska persona).
        $personLabel = function (string $path, string $fiziska, string $juridiska): \Closure {
            return fn (Get $get): string => ($get($path) ?? 'fiziska') === 'juridiska' ? $juridiska : $fiziska;
        };

        $sections = [
            Section::make()->columns(2)->schema([
                Forms\Components\Select::make('document_type')
                    ->label('Dokumenta veids')
                    ->options($service->documentOptions())
                    ->required()
                    ->live()
                    ->default(array_key_first($documents)),
                Forms\Components\Select::make('jurist_id')
                    ->label('Jurists')
                    ->options($service->juristOptions())
                    ->searchable()
                    ->required(),
            ]),

            Section::make('Juridiskie dati')->columns(2)->schema($manual),

            Section::make('Pārdevējs')->columns(2)->schema([
                Forms\Components\Select::make('seller.person_type')
                    ->label('Personas veids')
                    ->options(Client::PERSON_TYPES)
                    ->default($sellerDefaults['person_type'] ?? 'fiziska')
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('seller.name')
                    ->label($personLabel('seller.person_type', 'Vārds, uzvārds', 'Uzņēmuma nosaukums'))
                    ->required()
                    ->default($sellerDefaults['name'] ?? null),
                PersonasKodsInput::make('seller.personas_kods')
                    ->label($personLabel('seller.person_type', 'Personas kods', 'Reģistrācijas numurs'))
                    ->required()
                    ->plain(fn (Get $get): bool => $get('seller.person_type') === 'juridiska')
                    ->rules(fn (Get $get): array => $get('seller.person_type') === 'juridiska' ? [] : ['regex:/^\d{6}-\d{5}$/'])
                    ->default($sellerDefaults['personas_kods'] ?? null),
                Forms\Components\TextInput::make('seller.address')
                    ->label($personLabel('seller.person_type', 'Dzīvesvietas adrese', 'Juridiskā adrese'))
                    ->default($sellerDefaults['address'] ?? null),
                Forms\Components\TextInput::make('seller.bank_account')->label('Bankas konta numurs')->default($sellerDefaults['bank_account'] ?? null),
                Forms\Components\TextInput::make('seller.email')->label('E-pasts')->email()->default($sellerDefaults['email'] ?? null),
                PhoneInput::make('seller.phone')->label('Tālrunis')->maxLength(20)->rule(new Phone)->default($sellerDefaults['phone'] ?? null),
            ]),

            Section::make('Pircējs')->columns(2)->schema([
                Forms\Components\Select::make('buyer.person_type')
                    ->label('Personas veids')
                    ->options(Client::PERSON_TYPES)
                    ->default($buyerDefaults['person_type'] ?? 'fiziska')
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('buyer.name')
                    ->label($personLabel('buyer.person_type', 'Vārds, uzvārds', 'Uzņēmuma nosaukums'))
                    ->default($buyerDefaults['name'] ?? null),
                PersonasKodsInput::make('buyer.personas_kods')
                    ->label($personLabel('buyer.person_type', 'Personas kods', 'Reģistrācijas numurs'))
                    ->plain(fn (Get $get): bool => $get('buyer.person_type') === 'juridiska')
                    ->rules(fn (Get $get): array => $get('buyer.person_type') === 'juridiska' ? [] : ['regex:/^\d{6}-\d{5}$/'])
                    ->default($buyerDefaults['personas_kods'] ?? null),
                Forms\Components\TextInput::make('buyer.address')
                    ->label($personLabel('buyer.person_type', 'Dzīvesvietas adrese', 'Juridiskā adrese'))
                    ->default($buyerDefaults['address'] ?? null),
                Forms\Components\TextInput::make('buyer.bank_account')->label('Bankas konta numurs')->default($buyerDefaults['bank_account'] ?? null),
                Forms\Components\TextInput::make('buyer.email')->label('E-pasts')->email()->default($buyerDefaults['email'] ?? null),
                PhoneInput::make('buyer.phone')->label('Tālrunis')->maxLength(20)->rule(new Phone)->default($buyerDefaults['phone'] ?? null),
            ]),

            Section::make('Īpašums')->columns(2)->schema([
                Forms\Components\TextInput::make('property.address')->label('Adrese')->required()->default($property->address),
                Forms\Components\TextInput::make('property.kadastra_nr')->label('Kadastra numurs')->default($property->kadastra_nr),
                Forms\Components\TextInput::make('property.city')->label('Pilsēta / novads')->default($property->city),
                Forms\Components\TextInput::make('property.zip')->label('Pasta indekss')->default($property->zip),
            ]),
        ];

        return $sections;
    }

    /**
     * Izmet laukus, kas nav aktuāli izvēlētajam dokumenta veidam vai
     * finansējuma veidam.
     *
     * @param  array<string, mixed>  $legal
     * @return array<string, mixed>
     */
    private static function sanitizeLegal(string $type, array $legal): array
    {
        $purchase = in_array($type, ['pirkums', 'pirkums_nomaksa'], true);
        $installment = $type === 'pirkums_nomaksa';
        $bankCredit = $purchase && ($legal['financing_type'] ?? null) === 'Bankas kredīts';

        if (! $purchase) {
            foreach ([
                'payment_total', 'payment_down', 'payment_remaining', 'payment_plan',
                'payment_plan_first_day', 'financing_type', 'bank_name',
                'release_date',
            ] as $key) {
                unset($legal[$key]);
            }
        }

        if ($type === 'rokas_nauda') {
            unset($legal['hand_money_contract']);
        }

        // Rokas nauda pirkuma līgumā ir aktuāla tikai tad, ja līgums noslēgts.
        if ($purchase && ($legal['hand_money_contract'] ?? null) !== 'Jā') {
            unset($legal['hand_money_amount'], $legal['hand_money_date']);
        }

        if (! $bankCredit) {
            unset($legal['bank_name'], $legal['payment_down']);
        }

        if (! $installment) {
            unset($legal['payment_remaining'], $legal['payment_plan'], $legal['payment_plan_first_day']);
        }

        // Laulātā vārds ir aktuāls tikai tad, ja laulātais ir atzīmēts.
        if (($legal['seller_spouse'] ?? null) !== 'Ir') {
            unset($legal['spouse_name']);
        }

        return $legal;
    }

    private static function sellerOf(CrmProperty $property): ?Client
    {
        return $property->clients
            ->first(fn (Client $client): bool => $client->pivot->relation === 'seller');
    }

    private static function buyerOf(CrmProperty $property): ?Client
    {
        return $property->clients
            ->first(fn (Client $client): bool => $client->pivot->relation === 'buyer');
    }

    /** @param  array<string, mixed>  $data */
    private static function applyClientUpdates(?Client $client, array $data): void
    {
        if (! $client || $data === []) {
            return;
        }

        $updates = [];

        // person_type pirmais, lai name mutators redzētu juridisko personu.
        foreach (['person_type', 'name', 'personas_kods', 'address', 'bank_account', 'email', 'phone'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
            if ($value === '') {
                $value = null;
            }

            if ($client->getAttribute($key) !== $value) {
                $updates[$key] = $value;
            }
        }

        if ($updates !== []) {
            $client->update($updates);
        }
    }

    /** @param  array<string, mixed>  $data */
    private static function applyPropertyUpdates(CrmProperty $property, array $data): void
    {
        foreach (['address', 'kadastra_nr', 'city', 'zip'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
            $property->setAttribute($key, $value === '' ? null : $value);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private static function normalizedFields(array $data, array $keys): array
    {
        $fields = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = is_string($data[$key]) ? trim($data[$key]) : $data[$key];

            if ($value !== null && $value !== '') {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }
}
