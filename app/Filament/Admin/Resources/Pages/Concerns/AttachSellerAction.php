<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

use App\Models\Client;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

trait AttachSellerAction
{
    protected function getAttachSellerAction(): Actions\Action
    {
        return Actions\Action::make('attach_seller')
            ->label('Piesaistīt pārdevēju')
            ->icon('heroicon-o-user-plus')
            ->color('gray')
            ->form([
                Forms\Components\Select::make('client_id')
                    ->label('Pārdevējs')
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
            ])
            ->action(function (array $data): void {
                if (DB::table('client_crm_properties')
                    ->where('crm_property_id', $this->record->id)
                    ->where('relation', 'seller')
                    ->exists()
                ) {
                    Notification::make()->title('Pārdevējs jau ir piesaistīts')->warning()->send();

                    return;
                }

                $this->record->clients()->attach($data['client_id'], ['relation' => 'seller']);
                Notification::make()->title('Pārdevējs piesaistīts')->success()->send();
            });
    }

    protected function getAttachBuyerAction(): Actions\Action
    {
        return Actions\Action::make('attach_buyer')
            ->label('Piesaistīt pircēju')
            ->icon('heroicon-o-user-plus')
            ->color('gray')
            ->visible(fn () => ($this->record->status ?? null) === 'sold')
            ->form([
                Forms\Components\Select::make('client_id')
                    ->label('Pircējs')
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
                Forms\Components\Textarea::make('notes_md')
                    ->label('Piezīmes')
                    ->rows(2)
                    ->maxLength(1000),
            ])
            ->action(function (array $data): void {
                if (DB::table('client_crm_properties')
                    ->where('crm_property_id', $this->record->id)
                    ->where('relation', 'buyer')
                    ->exists()
                ) {
                    Notification::make()->title('Pircējs jau ir piesaistīts')->warning()->send();
                    return;
                }
                $this->record->clients()->attach($data['client_id'], ['relation' => 'buyer', 'notes_md' => $data['notes_md'] ?? null]);
                Notification::make()->title('Pircējs piesaistīts')->success()->send();
            });
    }
}
