<?php

namespace App\Filament\Admin\Resources\CrmPropertyResource\RelationManagers;

use App\Filament\Admin\Resources\Pages\Concerns\PievienotKlientuRelationAction;
use App\Models\Client;
use App\Models\ClientCrmProperty;
use App\Models\CrmProperty;
use App\Support\PhoneFormat;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;

class ClientsRelationManager extends RelationManager
{
    use PievienotKlientuRelationAction;

    protected static string $relationship = 'clients';

    protected static ?string $title = 'Piesaistītie klienti';

    protected string $view = 'filament.admin.resources.crm-property-resource.relation-managers.clients-relation-manager';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('id')
                ->label('Klients')
                ->searchable()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getSearchResultsUsing(fn (string $search): array => Client::query()
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->limit(20)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                ->required(),
            Forms\Components\Select::make('relation')
                ->label('Tips')
                ->options(fn () => $this->getOwnerRecord()->status === 'sold'
                    ? ['seller' => 'Pārdevējs', 'buyer' => 'Pircējs', 'tenant' => 'Īrnieks', 'landlord' => 'Izīrētājs', 'interested' => 'Interesents', 'contacted' => 'Sazināts']
                    : collect(ClientCrmProperty::RELATIONS)->except('buyer')->all())
                ->required(),
        ]);
    }

    /**
     * Allow attaching/detaching on the property VIEW page too — Filament
     * denies these actions on ViewRecord pages by default, which hid the
     * "Pievienot klientu" button in the Piesaistītie klienti section.
     */
    public function getDefaultActionAuthorizationResponse(Actions\Action $action): ?Response
    {
        if ($action instanceof Actions\AttachAction || $action instanceof Actions\DetachAction) {
            return null;
        }

        return parent::getDefaultActionAuthorizationResponse($action);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Klients')->sortable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->sortable()->wrap()->formatStateUsing(fn ($state) => PhoneFormat::display((string) $state)),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->sortable()->wrap(),
                Tables\Columns\TextColumn::make('pivot.relation')
                    ->label('Saistība')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'buyer' => 'Pircējs',
                        'seller' => 'Pārdevējs',
                        'tenant' => 'Īrnieks',
                        'landlord' => 'Izīrētājs',
                        'interested' => 'Interesents',
                        'contacted' => 'Sazināts',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('marketing_consent')
                    ->label('Mārketings')->boolean()->sortable(),
            ])
            ->headerActions([
                $this->getPievienotKlientuAction(),
            ])
            ->actions([
                Actions\Action::make('send_email')
                    ->label('Nosūtīt')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    // "Paldies par sadarbību" e-pasts ir aktuāls tikai
                    // pārdotiem īpašumiem, tāpēc poga redzama tikai sold.
                    ->visible(function (Client $record): bool {
                        $property = $this->getOwnerRecord();

                        return ! auth()->user()?->isPhoto()
                            && filled($record->email)
                            && $property instanceof CrmProperty
                            && $property->status === 'sold';
                    })
                    ->alpineClickHandler(function (Client $record): string {
                        $property = $this->getOwnerRecord();
                        $detail = json_encode([
                            'id' => $record->id,
                            'name' => (string) $record->name,
                            'email' => (string) $record->email,
                            'url' => route('properties.clients.send-email', [
                                'propertySlug' => $property->slug ?? $property->getKey(),
                                'client' => $record->id,
                            ]),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        return "window.dispatchEvent(new CustomEvent('pdc-open-client-email', { detail: {$detail} }))";
                    }),
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label('Rediģēt')
                        ->modalHeading('Rediģēt klientu')
                        ->modalSubmitActionLabel('Saglabāt')
                        ->color('gray'),
                    Actions\DetachAction::make()->label('Noņemt')->color('gray'),
                ])->color('gray'),
            ]);
    }
}
