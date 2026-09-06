<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\ClientCrmProperty;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class CrmPropertiesAsSellerRelationManager extends RelationManager
{
    protected static string $relationship = 'crmProperties';

    protected static ?string $title = 'Īpašumi pārdošanā';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-building-office-2';

    public function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('client_crm_properties')
                    ->whereRaw('client_crm_properties.crm_property_id = crm_properties.id')
                    ->where('client_crm_properties.client_id', $this->getOwnerRecord()->getKey())
                    ->where('client_crm_properties.relation', 'seller');
            });
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Īpašums')->sortable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('city')->label('Pilsēta')->sortable()->wrap(),
                Tables\Columns\TextColumn::make('kadastra_nr')->label('Kadastra nr.')->sortable()->placeholder('—')->wrap(),
                Tables\Columns\TextColumn::make('status')->label('Statuss')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => CrmProperty::STATUSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('price_eur')->label('Cena')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('pivot.relation')
                    ->label('Saistība')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ClientCrmProperty::RELATIONS[$state] ?? $state),
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Pievienot esošu īpašumu')
                    ->icon('heroicon-o-magnifying-glass-plus')
                    ->color('gray')
                    ->recordSelectSearchColumns(['title', 'city', 'kadastra_nr', 'id'])
                    ->recordSelectOptionsQuery(function ($query) {
                        return $query->where('status', '!=', 'sold')->limit(20);
                    })
                    ->schema(function (Actions\AttachAction $action): array {
                        $recordSelect = $action->getRecordSelect();

                        return [
                            $recordSelect,
                            Forms\Components\Hidden::make('relation')
                                ->default('seller'),
                            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
                        ];
                    })
                    ->before(function (array $data, $livewire): void {
                        $propertyId = $data['recordId'] ?? null;
                        $relation = $data['relation'] ?? null;
                        $clientId = $livewire->getOwnerRecord()->id;

                        if (! $propertyId || ! $relation) {
                            return;
                        }

                        $existingRelation = DB::table('client_crm_properties')
                            ->where('client_id', $clientId)
                            ->where('crm_property_id', $propertyId)
                            ->value('relation');

                        if ($existingRelation && $existingRelation !== $relation) {
                        }

                        $property = CrmProperty::find($propertyId);
                        if ($relation === 'buyer') {
                        }
                    }),
                Actions\Action::make('create_property')
                    ->label('Jauns īpašums')
                    ->icon('heroicon-o-plus')
                    ->color('gray')
                    ->modalHeading('Jauns īpašums')
                    ->modalSubmitActionLabel('Izveidot')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->label('Nosaukums')
                            ->required()
                            ->maxLength(200),
                        Forms\Components\Select::make('category')
                            ->label('Kategorija')
                            ->options(CrmProperty::CATEGORIES)
                            ->required(),
                        Forms\Components\TextInput::make('kadastra_nr')
                            ->label('Kadastra nr.')
                            ->maxLength(11)
                            ->minLength(11)
                            ->rules(['regex:/^\d{11}$/'])
                            ->extraInputAttributes([
                                'maxlength' => 11,
                                'inputmode' => 'numeric',
                                'pattern' => '\d{11}',
                                'x-on:input' => '$el.value = $el.value.replace(/\\D/g, \'\').slice(0, 11)',
                                'x-on:paste' => '$el.value = ($event.clipboardData || window.clipboardData).getData(\'text\').replace(/\\D/g, \'\').slice(0, 11); $event.preventDefault();',
                            ])
                            ->validationMessages([
                                'regex' => 'Kadastra nr. jābūt tieši 11 cipariem.',
                                'min' => 'Kadastra nr. jābūt tieši 11 cipariem.',
                                'max' => 'Kadastra nr. nedrīkst pārsniegt 11 ciparus.',
                            ]),
                        Forms\Components\TextInput::make('city')
                            ->label('Pilsēta')
                            ->maxLength(128),
                    ])
                    ->action(function (array $data): void {
                        $property = CrmProperty::create([
                            'title' => $data['title'],
                            'category' => $data['category'] ?? null,
                            'kadastra_nr' => $data['kadastra_nr'] ?? null,
                            'city' => $data['city'] ?? null,
                            'status' => 'draft',
                            'owner_user_id' => auth()->id(),
                        ]);

                        $this->getOwnerRecord()->crmProperties()->attach($property->id, ['relation' => 'seller']);

                        Notification::make()
                            ->title('Īpašums izveidots un pievienots')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\DetachAction::make()->label('Noņemt')->color('gray'),
                ])->color('gray'),
            ]);
    }
}
