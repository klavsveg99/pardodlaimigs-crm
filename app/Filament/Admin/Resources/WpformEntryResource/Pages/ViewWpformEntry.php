<?php

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\WpformEntryResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWpformEntry extends ViewRecord
{
    protected static string $resource = WpformEntryResource::class;

    protected string $view = 'filament.admin.resources.wpform-entry-resource.pages.view-wpform-entry';

    public function getTitle(): string
    {
        return 'Formas ieraksts';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_client')
                ->label('Ģenerēt klientu')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn () => $this->record->client_id === null)
                ->requiresConfirmation()
                ->modalHeading('Ģenerēt klientu no pieteikuma')
                ->modalDescription('Tiks izveidots jauns klients ar vārdu, e-pastu un tālruni no šī pieteikuma un pieteikums tiks piesaistīts jaunajam klientam.')
                ->modalSubmitActionLabel('Ģenerēt')
                ->action(function () {
                    $name = $this->record->fieldValue('Jūsu vārds') ?? '—';
                    $email = $this->record->fieldValue('E-pasts');
                    $phone = $this->record->fieldValue('Telefona numurs');

                    if ($email && Client::where('email', $email)->whereNull('gdpr_erased_at')->exists()) {
                        Notification::make()
                            ->title('Klients ar šo e-pastu jau eksistē')
                            ->body('Piesaistīts esošais klients.')
                            ->warning()
                            ->send();

                        $existing = Client::where('email', $email)->whereNull('gdpr_erased_at')->first();
                        $this->record->update(['client_id' => $existing->id]);

                        return;
                    }

                    $client = Client::create([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'source' => 'Tīmekļa vietne',
                    ]);

                    $this->record->update(['client_id' => $client->id]);

                    Notification::make()
                        ->title('Klients izveidots un piesaistīts')
                        ->body("Klients #{$client->id} · {$client->name}")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('link_client')
                ->label('Piesaistīt klientu')
                ->icon('heroicon-o-link')
                ->visible(fn () => $this->record->client_id === null)
                ->form([
                    Select::make('client_id')
                        ->label('Klients')
                        ->searchable()
                        ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                        ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['client_id' => $data['client_id']]);
                    Notification::make()
                        ->title('Klients piesaistīts')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('unlink_client')
                ->label('Atsaistīt klientu')
                ->icon('heroicon-o-link-slash')
                ->color('warning')
                ->visible(fn () => $this->record->client_id !== null)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['client_id' => null]);
                    Notification::make()
                        ->title('Klients atsaistīts')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make()->label('Dzēst'),
        ];
    }
}
