<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

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
 * "Jurista dokuments" darbība īpašuma skatā: izvēlas dokumenta veidu un
 * juristu, ļauj aizpildīt trūkstošos datus, pārbauda obligātos laukus un
 * nosūta strukturētu e-pastu. Pieejama tikai pārdotam īpašumam ar
 * piesaistītu pārdevēju (pircējs nav obligāts).
 */
trait LawyerDocumentAction
{
    protected function getLawyerDocumentAction(): Actions\Action
    {
        return Actions\Action::make('lawyer_document')
            ->label('Jurista dokuments')
            ->icon('heroicon-o-scale')
            ->color('gray')
            ->visible(fn (): bool => $this->lawyerProperty()?->status === 'sold'
                && $this->lawyerSeller() !== null)
            ->modalHeading('Nosūtīt juristam')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Nosūtīt')
            // Divi soļi: datu aizpilde un e-pasta priekšskatījums pirms
            // nosūtīšanas.
            ->steps(fn (): array => $this->lawyerSteps())
            ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard
                ->nextAction(fn (Actions\Action $action): Actions\Action => $action->label('Tālāk'))
                ->previousAction(fn (Actions\Action $action): Actions\Action => $action->label('Atpakaļ')))
            ->action(fn (array $data) => $this->sendLawyerDocument($data));
    }

    protected function lawyerProperty(): ?CrmProperty
    {
        $record = $this->record ?? null;

        return $record instanceof CrmProperty ? $record : null;
    }

    protected function lawyerSeller(): ?Client
    {
        return $this->lawyerProperty()?->clients
            ->first(fn (Client $client): bool => $client->pivot->relation === 'seller');
    }

    protected function lawyerBuyer(): ?Client
    {
        return $this->lawyerProperty()?->clients
            ->first(fn (Client $client): bool => $client->pivot->relation === 'buyer');
    }

    /** @return array<int, Step> */
    protected function lawyerSteps(): array
    {
        return [
            Step::make('Dokumenta dati')
                ->schema($this->lawyerFormFields()),
            Step::make('Priekšskatījums')
                ->description('Pārbaudi e-pastu pirms nosūtīšanas')
                ->schema([$this->lawyerPreview()]),
        ];
    }

    /** E-pasta priekšskatījums no aizpildītajām formas vērtībām. */
    protected function lawyerPreview(): View
    {
        $property = $this->lawyerProperty();

        return View::make('filament.admin.partials.lawyer-email-preview')
            ->viewData(function (Get $get) use ($property): array {
                if (! $property) {
                    return ['to' => '', 'jurist' => '', 'subject' => '', 'html' => '', 'attachments' => 0];
                }

                return app(LawyerDocumentService::class)->preview([
                    'document_type' => $get('document_type'),
                    'jurist_id' => $get('jurist_id'),
                    'legal' => $get('legal') ?? [],
                    'seller' => $get('seller') ?? [],
                    'buyer' => $get('buyer') ?? [],
                    'property' => $get('property') ?? [],
                ], $property);
            })
            ->columnSpanFull();
    }

    /** @return array<int, mixed> */
    protected function lawyerFormFields(): array
    {
        $service = app(LawyerDocumentService::class);
        $property = $this->lawyerProperty();
        $seller = $this->lawyerSeller();
        $buyer = $this->lawyerBuyer();
        $legal = $property?->legal_data ?? [];
        $documents = $service->documentTypes();

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
                ->default($legal[$key] ?? null)
                ->required(fn (Get $get): bool => in_array($key, $documents[$get('document_type')]['required'] ?? [], true));

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

        $juristOptions = $service->juristOptions();

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
                    ->options($juristOptions)
                    ->searchable()
                    ->required(),
            ]),

            Section::make('Juridiskie dati')->columns(2)->schema($manual),

            Section::make('Pārdevējs')->columns(2)->visible($seller !== null)->schema([
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
                Forms\Components\TextInput::make('property.address')->label('Adrese')->required()->default($property?->address),
                Forms\Components\TextInput::make('property.kadastra_nr')->label('Kadastra numurs')->default($property?->kadastra_nr),
                Forms\Components\TextInput::make('property.city')->label('Pilsēta / novads')->default($property?->city),
                Forms\Components\TextInput::make('property.zip')->label('Pasta indekss')->default($property?->zip),
            ]),
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function sendLawyerDocument(array $data): void
    {
        $property = $this->lawyerProperty();

        if (! $property) {
            return;
        }

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

        $this->applyClientUpdates($this->lawyerSeller(), is_array($data['seller'] ?? null) ? $data['seller'] : []);
        $buyerClient = $this->lawyerBuyer();
        $buyerData = is_array($data['buyer'] ?? null) ? $data['buyer'] : [];
        $this->applyClientUpdates($buyerClient, $buyerData);
        $this->applyPropertyUpdates($property, is_array($data['property'] ?? null) ? $data['property'] : []);

        $legal = is_array($data['legal'] ?? null) ? $data['legal'] : [];

        // Pircējs nav piesaistīts kā klients — saglabājam manuāli ievadītos
        // datus, lai tie nepazūd un nonāk e-pastā.
        if ($buyerClient === null) {
            $buyerBlock = $this->normalizedFields($buyerData, ['name', 'personas_kods', 'address', 'bank_account', 'email', 'phone']);
            if ($buyerBlock !== []) {
                $legal['buyer'] = $buyerBlock;
            }
        }

        $legalData = array_merge(is_array($property->legal_data) ? $property->legal_data : [], $legal);

        // Laulātā vārds ir aktuāls tikai tad, ja laulātais ir atzīmēts;
        // pretējā gadījumā veco vērtību izmetam, lai tā nepaliek e-pastā.
        if (($legalData['seller_spouse'] ?? null) !== 'Ir') {
            unset($legalData['spouse_name']);
        }

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

    /** @param  array<string, mixed>  $data */
    protected function applyClientUpdates(?Client $client, array $data): void
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
    protected function applyPropertyUpdates(CrmProperty $property, array $data): void
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
    protected function normalizedFields(array $data, array $keys): array
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
