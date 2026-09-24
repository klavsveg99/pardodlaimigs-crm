<?php

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\WpformEntryResource;
use App\Models\Client;
use App\Models\FollowUpLead;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWpformEntry extends ViewRecord
{
    protected static string $resource = WpformEntryResource::class;

    protected string $view = 'filament.admin.resources.wpform-entry-resource.pages.view-wpform-entry';

    protected static ?string $title = 'status';

    public function getTitle(): string
    {
        return 'Formas ieraksts';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_status')
                ->label(fn () => 'Statuss: '.(WpformEntryResource::STATUSES[$this->record->status] ?? $this->record->status ?? '—'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                // Deleted entries go back through the restore action (which
                // clears the tombstone), never a raw status change.
                ->visible(fn (): bool => $this->record->status !== 'deleted')
                ->form([
                    Select::make('status')
                        ->label('Statuss')
                        ->options(WpformEntryResource::SELECTABLE_STATUSES)
                        ->default(fn () => $this->record->status)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['status' => $data['status']]);
                    Notification::make()
                        ->title('Statuss mainīts')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('generate_client')
                ->label('Ģenerēt klientu')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
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
                        $this->record->update(['client_id' => $existing->id, 'status' => 'klients_pievienots']);

                        return;
                    }

                    $client = Client::create([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'source' => 'Tīmekļa vietne',
                    ]);

                    $this->record->update(['client_id' => $client->id, 'status' => 'klients_pievienots']);

                    Notification::make()
                        ->title('Klients izveidots un piesaistīts')
                        ->body("Klients #{$client->id} · {$client->name}")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('to_follow_up')
                ->label('Uz Follow Up līdi')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('gray')
                ->visible(fn (): bool => $this->record->status !== 'deleted'
                    && ! FollowUpLead::where('source_wpform_entry_id', $this->record->id)->exists())
                ->requiresConfirmation()
                ->modalHeading('Izveidot Follow Up līdi')
                ->modalDescription('Tiks izveidots/piesaistīts klients un izveidots Follow Up līdis, kuram CRM atgādinās regulāri sazināties, līdz sadarbība tiek uzsākta.')
                ->modalSubmitActionLabel('Izveidot')
                ->action(function (): void {
                    $client = $this->record->client;

                    if (! $client) {
                        $email = $this->record->fieldValue('E-pasts');
                        $client = $email
                            ? Client::where('email', $email)->whereNull('gdpr_erased_at')->first()
                            : null;
                    }

                    if (! $client) {
                        $client = Client::create([
                            'name' => $this->record->fieldValue('Jūsu vārds') ?? '—',
                            'email' => $this->record->fieldValue('E-pasts'),
                            'phone' => $this->record->fieldValue('Telefona numurs'),
                            'source' => 'Tīmekļa vietne',
                            'owner_user_id' => auth()->user()?->can('manage') ? null : auth()->id(),
                        ]);
                    }

                    $this->record->update(['client_id' => $client->id, 'status' => 'follow_up']);

                    FollowUpLead::create([
                        'client_id' => $client->id,
                        'owner_user_id' => $client->owner_user_id ?: auth()->id(),
                        'source_wpform_entry_id' => $this->record->id,
                        'conversation_started_at' => ($this->record->created_at ?? now())->toDateString(),
                        'next_contact_at' => today()->addDays(7)->toDateString(),
                        'cadence_days' => 7,
                        'status' => 'active',
                    ]);

                    Notification::make()
                        ->title('Follow Up līdis izveidots')
                        ->body($client->name)
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
                        ->getSearchResultsUsing(fn (string $search): array => Client::query()
                            ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%"))
                            ->orderBy('name')
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['client_id' => $data['client_id'], 'status' => 'klients_pievienots']);
                    Notification::make()
                        ->title('Klients piesaistīts')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('unlink_client')
                ->label('Atsaistīt klientu')
                ->icon('heroicon-o-link-slash')
                ->color('gray')
                ->visible(fn () => $this->record->client_id !== null)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['client_id' => null, 'status' => 'new']);
                    Notification::make()
                        ->title('Klients atsaistīts')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make()->label('Dzēst')->color('gray'),
        ];
    }
}
