<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewCrmProperty extends ViewRecord
{
    protected static string $resource = CrmPropertyResource::class;

    protected string $view = 'filament.admin.resources.crm-property-resource.pages.view-crm-property';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('attach_seller')
                ->label('Piesaistīt pārdevēju')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
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
                    if (DB::table('client_crm_properties')->where('crm_property_id', $this->record->id)->where('relation', 'seller')->exists()) {
                        Notification::make()->title('Pārdevējs jau ir piesaistīts')->warning()->send();

                        return;
                    }

                    $this->record->clients()->attach($data['client_id'], ['relation' => 'seller']);
                    Notification::make()->title('Pārdevējs piesaistīts')->success()->send();
                }),
            Actions\EditAction::make()->label('Rediģēt'),
            Actions\Action::make('open_site')
                ->label('Atvērt vietnē')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->public_url)
                ->openUrlInNewTab(),
        ];
    }
}
