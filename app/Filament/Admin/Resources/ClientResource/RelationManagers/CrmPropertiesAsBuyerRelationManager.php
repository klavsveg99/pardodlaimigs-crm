<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\ClientCrmProperty;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrmPropertiesAsBuyerRelationManager extends RelationManager
{
    protected static string $relationship = 'crmProperties';

    protected static ?string $title = 'Pircēja īpašumi';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-shopping-cart';

    public function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('client_crm_properties')
                    ->whereRaw('client_crm_properties.crm_property_id = crm_properties.id')
                    ->where('client_crm_properties.client_id', $this->getOwnerRecord()->getKey())
                    ->where('client_crm_properties.relation', 'buyer');
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
                    ->label('Pievienot CRM īpašumu')
                    ->color('primary')
                    ->recordSelectSearchColumns(['title', 'city', 'kadastra_nr', 'id'])
                    ->schema(function (Actions\AttachAction $action): array {
                        $recordSelect = $action->getRecordSelect();

                        return [
                            $recordSelect,
                            Forms\Components\Hidden::make('relation')
                                ->default('buyer'),
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

                        // Note: Same client can be both seller and buyer on same property per business rules

                        $property = CrmProperty::find($propertyId);
                        if ($relation === 'buyer') {
                            if (! $property || $property->status !== 'sold') {
                                throw ValidationException::withMessages([
                                    'data.relation' => 'Pircēju drīkst piesaistīt tikai pārdotam īpašumam.',
                                ]);
                            }

                            $hasSeller = DB::table('client_crm_properties')
                                ->where('crm_property_id', $propertyId)
                                ->where('relation', 'seller')
                                ->exists();

                            if (! $hasSeller) {
                                throw ValidationException::withMessages([
                                    'data.relation' => 'Īpašumam vispirms jābūt piesaistītam pārdevējam.',
                                ]);
                            }

                            if (! $livewire->getOwnerRecord()->marketing_consent) {
                                throw ValidationException::withMessages([
                                    'data.relation' => 'Lai piesaistītu pircēju pārdotam īpašumam, klientam jābūt mārketinga piekrišanai.',
                                ]);
                            }
                        }
                    })
                    ->after(function (array $data, $livewire): void {
                        $c = $livewire->getOwnerRecord();
                        if (empty($c->client_type)) $c->update(['client_type' => 'buyer']);
                    }),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\DetachAction::make()->label('Noņemt')->color('gray'),
                ]),
            ]);
    }
}