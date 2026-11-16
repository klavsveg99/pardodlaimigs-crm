<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Filament\Admin\Resources\ClientResource;
use App\Models\Client;
use App\Models\ClientCrmProperty;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Attach action for the property's associated clients table.
 *
 * Business rules: one seller, one buyer, unlimited "interesenti". A relation
 * that is already filled is hidden from the picker instead of blocking the
 * whole action, so another interested client can always be added.
 */
trait PievienotKlientuRelationAction
{
    protected function getPievienotKlientuAction(): Actions\Action
    {
        return Actions\Action::make('piesaisit_klientu')
            ->label('Pievienot klientu')
            ->color('gray')
            ->icon('heroicon-o-user-plus')
            ->modalHeading('Pievienot klientu')
            ->visible(fn (): bool => ! (auth()->user()?->isPhoto() ?? false))
            ->modalSubmitActionLabel('Pievienot')
            ->extraModalFooterActions([
                Actions\Action::make('izveidot_jaunu_klientu')
                    ->label('Izveidot jaunu klientu')
                    ->icon('heroicon-o-plus')
                    ->color('gray')
                    ->url(fn (): string => ClientResource::getUrl('create'))
                    ->openUrlInNewTab(),
            ])
            ->form(function (): array {
                $property = $this->getPievienotKlientuProperty();
                $relations = $this->availableClientRelations($property);

                $radio = Forms\Components\Radio::make('relation')
                    ->label('Tips')
                    ->options($relations)
                    ->required()
                    ->default(array_key_first($relations))
                    ->inline()
                    ->live();

                if (! $property->hasSeller()) {
                    $radio->helperText('Pircēju var pievienot tikai pārdotam īpašumam. Interesentu skaits nav ierobežots.');
                }

                return [
                    $radio,

                    Forms\Components\Select::make('client_id')
                        ->label('Klients')
                        ->searchable()
                        ->required()
                        ->options(function (Get $get) use ($property): array {
                            return $this->getPievienotKlientuOptions($property, $get('relation') ?? 'interested', null);
                        })
                        ->getSearchResultsUsing(function (string $search, Get $get) use ($property): array {
                            return $this->getPievienotKlientuOptions($property, $get('relation') ?? 'interested', $search);
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes_md')
                        ->label('Piezīme')
                        ->helperText('Redzama tikai šī īpašuma saistīto klientu tabulā.')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ];
            })
            ->action(function (array $data): void {
                $property = $this->getPievienotKlientuProperty();
                $relation = (string) ($data['relation'] ?? 'interested');
                $clientId = $data['client_id'] ?? null;
                $notes = filled($data['notes_md'] ?? null) ? (string) $data['notes_md'] : null;

                if (empty($clientId)) {
                    throw ValidationException::withMessages([
                        'client_id' => 'Izvēlieties klientu.',
                    ]);
                }

                if (! array_key_exists($relation, $this->availableClientRelations($property))) {
                    throw ValidationException::withMessages([
                        'relation' => match ($relation) {
                            'seller' => 'Šim īpašumam jau ir piesaistīts pārdevējs.',
                            'buyer' => $property->status === 'sold'
                                ? 'Šim īpašumam jau ir piesaistīts pircējs.'
                                : 'Pircēju drīkst piesaistīt tikai pārdotam īpašumam.',
                            default => 'Šo saistību šim īpašumam nevar pievienot.',
                        },
                    ]);
                }

                $already = DB::table('client_crm_properties')
                    ->where('crm_property_id', $property->id)
                    ->where('client_id', $clientId)
                    ->where('relation', $relation)
                    ->exists();
                if ($already) {
                    throw ValidationException::withMessages([
                        'client_id' => (ClientCrmProperty::RELATIONS[$relation] ?? 'Klients').' jau ir piesaistīts šim īpašumam.',
                    ]);
                }

                $property->clients()->attach($clientId, [
                    'relation' => $relation,
                    'notes_md' => $notes,
                ]);

                $client = Client::find($clientId);
                Notification::make()
                    ->title((ClientCrmProperty::RELATIONS[$relation] ?? 'Klients').' piesaistīts: '.$client?->name)
                    ->success()
                    ->send();
            });
    }

    /**
     * Relations that can still be added: seller/buyer only while free, and
     * buyer only on a sold property. Interested is always available.
     *
     * @return array<string, string>
     */
    protected function availableClientRelations(CrmProperty $property): array
    {
        $relations = [];

        if (! $property->hasSeller()) {
            $relations['seller'] = ClientCrmProperty::RELATIONS['seller'];
        }

        if ($property->status === 'sold' && ! $property->hasBuyer()) {
            $relations['buyer'] = ClientCrmProperty::RELATIONS['buyer'];
        }

        $relations['interested'] = ClientCrmProperty::RELATIONS['interested'];

        return $relations;
    }

    /**
     * Existing-client options, excluding clients already attached to the
     * property with the chosen relation.
     */
    protected function getPievienotKlientuOptions(CrmProperty $property, string $relation, ?string $search): array
    {
        return Client::query()
            ->whereNotExists(
                DB::table('client_crm_properties')
                    ->whereColumn('client_crm_properties.client_id', 'clients.id')
                    ->where('crm_property_id', $property->id)
                    ->where('relation', $relation)
            )
            ->when(
                $search !== null && $search !== '',
                fn ($q) => $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
            )
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Resolve the CrmProperty instance depending on context.
     */
    protected function getPievienotKlientuProperty(): CrmProperty
    {
        if (method_exists($this, 'getOwnerRecord')) {
            $rec = $this->getOwnerRecord();
            if ($rec instanceof CrmProperty) {
                return $rec;
            }
        }

        if (method_exists($this, 'getRecord')) {
            try {
                $rec = $this->getRecord();
                if ($rec instanceof CrmProperty) {
                    return $rec;
                }
            } catch (\Throwable) {
                // Component not mounted yet - fall through.
            }
        }

        if (isset($this->record) && $this->record instanceof CrmProperty) {
            return $this->record;
        }

        throw new \RuntimeException('PievienotKlientuRelationAction requires a CrmProperty context.');
    }
}
