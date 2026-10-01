<?php

declare(strict_types=1);

namespace App\Filament\Admin\Actions;

use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Izpilditajs;
use App\Services\Lawyer\LawyerDocumentService;
use App\Services\Mail\EmailTooLargeException;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;

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
                && $record->hasSeller()
                && ! auth()->user()?->isPhoto())
            ->modalHeading('Nosūtīt juristam')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Nosūtīt')
            // Divi soļi: datu aizpilde un e-pasta priekšskatījums pirms
            // nosūtīšanas.
            ->steps(fn (CrmProperty $record): array => self::steps($record))
            ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard
                ->nextAction(fn (Actions\Action $action): Actions\Action => $action->label('Tālāk'))
                ->previousAction(fn (Actions\Action $action): Actions\Action => $action->label('Atpakaļ')))
            ->action(fn (array $data, CrmProperty $record) => self::send($record, $data));
    }

    /** @return array<int, Step> */
    private static function steps(CrmProperty $property): array
    {
        return [
            Step::make('Dokumenta dati')
                ->schema(self::formFields($property)),
            Step::make('Priekšskatījums')
                ->description('Pārbaudi e-pastu pirms nosūtīšanas')
                ->schema([self::preview($property)]),
        ];
    }

    /** E-pasta priekšskatījums no aizpildītajām formas vērtībām. */
    private static function preview(CrmProperty $property): View
    {
        return View::make('filament.admin.partials.lawyer-email-preview')
            ->viewData(function (Get $get) use ($property): array {
                $type = (string) $get('document_type');

                return app(LawyerDocumentService::class)->preview([
                    'document_type' => $type,
                    'jurist_id' => $get('jurist_id'),
                    'legal' => self::sanitizeLegal($type, $get('legal') ?? []),
                    'seller' => $get('seller') ?? [],
                    'buyer' => $get('buyer') ?? [],
                    'property' => $get('property') ?? [],
                ], $property);
            })
            ->columnSpanFull();
    }

    /** @return array<int, mixed> */
    private static function formFields(CrmProperty $property): array
    {
        $service = app(LawyerDocumentService::class);
        $documents = $service->documentTypes();
        $seller = self::sellerOf($property);
        $buyer = self::buyerOf($property);
        $legal = is_array($property->legal_data) ? $property->legal_data : [];

        // Ja pircējs nav piesaistīts kā klients, datus ņemam no iepriekš
        // aizpildītā manuālā bloka.
        $buyerDefaults = $buyer
            ? [
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
            'altum_guarantee' => $isPurchase,
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

            // "Atlikusī summa/izpirkums", "Maksājumu grafiks" un "Piezīmes"
            // aizņem visu rindu, lai 2. kolonna nepaliktu tukša.
            if (in_array($key, ['payment_remaining', 'payment_plan', 'notes'], true)) {
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

        return [
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
                Forms\Components\TextInput::make('seller.name')->label('Vārds, uzvārds')->required()->default($seller?->name),
                Forms\Components\TextInput::make('seller.personas_kods')->label('Personas kods')->required()->default($seller?->personas_kods),
                Forms\Components\TextInput::make('seller.address')->label('Dzīvesvietas adrese')->default($seller?->address),
                Forms\Components\TextInput::make('seller.bank_account')->label('Bankas konta numurs')->default($seller?->bank_account),
                Forms\Components\TextInput::make('seller.email')->label('E-pasts')->email()->default($seller?->email),
                Forms\Components\TextInput::make('seller.phone')->label('Tālrunis')->default($seller?->phone),
            ]),

            Section::make('Pircējs')->columns(2)->schema([
                Forms\Components\TextInput::make('buyer.name')->label('Vārds, uzvārds')->default($buyerDefaults['name'] ?? null),
                Forms\Components\TextInput::make('buyer.personas_kods')->label('Personas kods')->default($buyerDefaults['personas_kods'] ?? null),
                Forms\Components\TextInput::make('buyer.address')->label('Dzīvesvietas adrese')->default($buyerDefaults['address'] ?? null),
                Forms\Components\TextInput::make('buyer.bank_account')->label('Bankas konta numurs')->default($buyerDefaults['bank_account'] ?? null),
                Forms\Components\TextInput::make('buyer.email')->label('E-pasts')->email()->default($buyerDefaults['email'] ?? null),
                Forms\Components\TextInput::make('buyer.phone')->label('Tālrunis')->default($buyerDefaults['phone'] ?? null),
            ]),

            Section::make('Īpašums')->columns(2)->schema([
                Forms\Components\TextInput::make('property.address')->label('Adrese')->required()->default($property->address),
                Forms\Components\TextInput::make('property.kadastra_nr')->label('Kadastra numurs')->default($property->kadastra_nr),
                Forms\Components\TextInput::make('property.city')->label('Pilsēta / novads')->default($property->city),
                Forms\Components\TextInput::make('property.zip')->label('Pasta indekss')->default($property->zip),
            ]),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private static function send(CrmProperty $property, array $data): void
    {
        $type = (string) ($data['document_type'] ?? '');
        $jurist = Izpilditajs::query()
            ->where('category', 'Jurists')
            ->find($data['jurist_id'] ?? 0);

        if (! $jurist || blank($jurist->email)) {
            Notification::make()
                ->title('Jurists nav atrasts vai tam nav norādīts e-pasts')
                ->danger()
                ->send();

            return;
        }

        self::applyClientUpdates(self::sellerOf($property), is_array($data['seller'] ?? null) ? $data['seller'] : []);
        $buyerClient = self::buyerOf($property);
        $buyerData = is_array($data['buyer'] ?? null) ? $data['buyer'] : [];
        self::applyClientUpdates($buyerClient, $buyerData);
        self::applyPropertyUpdates($property, is_array($data['property'] ?? null) ? $data['property'] : []);

        $legal = is_array($data['legal'] ?? null) ? $data['legal'] : [];

        // Pircējs nav piesaistīts kā klients — saglabājam manuāli ievadītos
        // datus, lai tie nepazūd un nonāk e-pastā.
        if ($buyerClient === null) {
            $buyerBlock = self::normalizedFields($buyerData, ['name', 'personas_kods', 'address', 'bank_account', 'email', 'phone']);
            if ($buyerBlock !== []) {
                $legal['buyer'] = $buyerBlock;
            }
        }

        // Apvienojam ar jau saglabātajiem datiem un izmetam laukus, kas
        // izvēlētajam dokumenta veidam vairs nav aktuāli (piem. bankas dati
        // pārslēdzoties uz pašu līdzekļiem), lai vecās vērtības nepaliek
        // e-pastā.
        $legalData = array_merge(is_array($property->legal_data) ? $property->legal_data : [], $legal);
        $legalData = self::sanitizeLegal($type, $legalData);

        $property->legal_data = $legalData;
        $property->save();

        try {
            $request = app(LawyerDocumentService::class)->send(
                $property->refresh(),
                $jurist,
                $type,
                is_array($property->legal_data) ? $property->legal_data : [],
                auth()->user(),
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
                'payment_plan_first_day', 'financing_type', 'bank_name', 'altum_guarantee',
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

        foreach (['name', 'personas_kods', 'address', 'bank_account', 'email', 'phone'] as $key) {
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
