<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Models\Client;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single AttachAction for the property's Piesaistītie klienti
 * relation manager / view page.  Simple interface: pick an EXISTING
 * client and choose the relation (Pārdevējs / Pircējs).  Pircējs is
 * only selectable when the property status is Pārdots (sold) — the
 * hint tooltip on the field explains this.
 */
trait PievienotKlientuRelationAction
{
    /**
     * Returns a Filament `Action` (not `AttachAction`) that can be
     * mounted from a page-level blade via `wire:click="mountAction(...)"`
     * or used as a relation manager header action.
     */
    protected function getPievienotKlientuAction(): Actions\Action
    {
        return Actions\Action::make('piesaisit_klientu')
            ->label('Pievienot klientu')
            ->color('gray')
            ->icon('heroicon-o-user-plus')
            ->modalHeading('Pievienot klientu')
            ->modalDescription('Piesaistiet esošu klientu īpašumam kā pārdevēju vai pircēju.')
            ->modalSubmitActionLabel('Pievienot')
            ->form(function (): array {
                $property = $this->getPievienotKlientuProperty();
                $isSold = ($property?->status ?? null) === 'sold';

                $radio = Forms\Components\Radio::make('relation')
                    ->label('Tips')
                    ->options($isSold
                        ? ['seller' => 'Pārdevējs', 'buyer' => 'Pircējs']
                        : ['seller' => 'Pārdevējs'])
                    ->required()
                    ->default('seller')
                    ->inline()
                    ->live();

                if (! $isSold) {
                    $radio->helperText('Pircējs pieejams tikai «Pārdots» statusa īpašumiem.');
                }

                return [
                    $radio,

                    Forms\Components\Select::make('client_id')
                        ->label('Klients')
                        ->searchable()
                        ->required()
                        ->options(function (Get $get) use ($property): array {
                            return $this->getPievienotKlientuOptions($property, $get('relation') ?? 'seller', null);
                        })
                        ->getSearchResultsUsing(function (string $search, Get $get) use ($property): array {
                            return $this->getPievienotKlientuOptions($property, $get('relation') ?? 'seller', $search);
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                        ->columnSpanFull(),
                ];
            })
            ->action(function (array $data): void {
                $property = $this->getPievienotKlientuProperty();
                $relation = $data['relation'] ?? 'seller';
                $clientId = $data['client_id'] ?? null;

                if (empty($clientId)) {
                    throw ValidationException::withMessages([
                        'client_id' => 'Izvēlieties klientu.',
                    ]);
                }

                if ($relation === 'buyer' && $property->status !== 'sold') {
                    throw ValidationException::withMessages([
                        'relation' => 'Pircēju drīkst piesaistīt tikai pārdotam īpašumam.',
                    ]);
                }

                $already = DB::table('client_crm_properties')
                    ->where('crm_property_id', $property->id)
                    ->where('client_id', $clientId)
                    ->where('relation', $relation)
                    ->exists();
                if ($already) {
                    $verb = $relation === 'buyer' ? 'Pircējs' : 'Pārdevējs';
                    throw ValidationException::withMessages([
                        'client_id' => $verb.' jau ir piesaistīts šim īpašumam.',
                    ]);
                }

                $property->clients()->attach($clientId, ['relation' => $relation]);

                $client = Client::find($clientId);
                $verb = $relation === 'buyer' ? 'Pircējs' : 'Pārdevējs';
                Notification::make()
                    ->title($verb.' piesaistīts: '.$client?->name)
                    ->success()
                    ->send();
            });
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
                // Component not mounted yet — fall through.
            }
        }

        if (isset($this->record) && $this->record instanceof CrmProperty) {
            return $this->record;
        }

        throw new \RuntimeException('PievienotKlientuRelationAction requires a CrmProperty context.');
    }
}
