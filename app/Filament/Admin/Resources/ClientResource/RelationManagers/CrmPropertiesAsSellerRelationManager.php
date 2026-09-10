<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Models\ClientCrmProperty;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class CrmPropertiesAsSellerRelationManager extends RelationManager
{
    protected static string $relationship = 'crmProperties';

    protected static ?string $title = 'Īpašumi pārdošanā';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-building-office-2';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
        ]);
    }

    /**
     * Allow attaching/detaching on the client VIEW page too — Filament denies
     * these actions on ViewRecord pages by default, which hid the buttons.
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
            // Constrain the JOINED pivot rows to seller only. NOTE: this must
            // be done via modifyQueryUsing — the RM table query comes from the
            // Eloquent relationship and a getEloquentQuery() override on the
            // relation manager is never applied by the table pipeline. Without
            // this, a client who is both Pārdevējs and Pircējs on the same
            // property appears twice (once with the wrong relation badge).
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('client_crm_properties.relation', 'seller'))
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Īpašums')->sortable()->weight('bold')->wrap()
                    ->url(fn (CrmProperty $record) => CrmPropertyResource::getUrl('view', ['record' => $record])),
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
                    ->label('Pievienot īpašumu')
                    ->modalHeading('Pievienot īpašumu')
                    ->modalSubmitActionLabel('Pievienot')
                    ->icon('heroicon-o-magnifying-glass-plus')
                    ->color('gray')
                    ->recordTitle(fn (CrmProperty $record): string => $record->selection_label)
                    ->recordSelectSearchColumns(['title', 'city', 'kadastra_nr', 'id'])
                    ->recordSelectOptionsQuery(function ($query) {
                        return $query->where('status', '!=', 'sold')->limit(20);
                    })
                    ->schema(function (Actions\AttachAction $action): array {
                        $recordSelect = $action->getRecordSelect()
                            ->options(fn () => CrmProperty::query()
                                ->where('status', '!=', 'sold')
                                ->orderBy('title')
                                ->get()
                                ->mapWithKeys(fn (CrmProperty $p) => [$p->id => $p->selection_label])
                                ->all())
                            ->searchable(false);

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
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\DetachAction::make()->label('Noņemt')->color('gray'),
                ])->color('gray'),
            ]);
    }
}
