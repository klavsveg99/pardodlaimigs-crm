<?php

namespace App\Filament\Admin\Resources\CrmPropertyResource\RelationManagers;

use App\Filament\Admin\Resources\Pages\Concerns\PievienotKlientuRelationAction;
use App\Models\Client;
use App\Models\ClientCrmProperty;
use App\Models\CrmProperty;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Support\PhoneFormat;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

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
                    ? ClientCrmProperty::RELATIONS
                    : collect(ClientCrmProperty::RELATIONS)->except('buyer')->all())
                ->required(),
            Forms\Components\Textarea::make('notes_md')
                ->label('Piezīme')
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
        ]);
    }

    /**
     * Allow attaching/detaching on the property VIEW page too - Filament
     * denies these actions on ViewRecord pages by default, which hid the
     * "Pievienot klientu" button in the Piesaistītie klienti section.
     */
    public function getDefaultActionAuthorizationResponse(Actions\Action $action): ?Response
    {
        if ($action instanceof Actions\AttachAction
            || $action instanceof Actions\DetachAction
            || $action instanceof Actions\DetachBulkAction) {
            return null;
        }

        return parent::getDefaultActionAuthorizationResponse($action);
    }

    /** Refresh the offer date after the popup sends. */
    #[On('pdc-offer-sent')]
    public function offerSent(): void
    {
        // Re-render is the point.
    }

    public function table(Table $table): Table
    {
        return $table
            // A property can have one seller, one buyer and any number of
            // interested clients, so do not paginate/filter the short list.
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Klients')->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->wrap()->formatStateUsing(fn ($state) => PhoneFormat::display((string) $state)),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->wrap(),
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
                Tables\Columns\TextColumn::make('pivot.notes_md')
                    ->label('Piezīme')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (Client $record): ?string => filled($record->pivot?->notes_md)
                        ? (string) $record->pivot->notes_md
                        : null)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('offer_sent_at')
                    ->label('Piedāvājums')
                    ->state(fn (Client $record): ?string => $this->offerSentAt((int) $record->id)?->format('d.m.Y'))
                    ->icon(fn ($state): ?string => filled($state) ? 'heroicon-o-paper-airplane' : null)
                    ->placeholder('-')
                    ->tooltip(fn (Client $record): ?string => ($sentAt = $this->offerSentAt((int) $record->id))
                        ? 'Piedāvājums nosūtīts '.$sentAt->format('d.m.Y H:i')
                        : null),
            ])
            ->filters([])
            ->defaultSort(null)
            ->headerActions([
                $this->getPievienotKlientuAction(),
                Actions\Action::make('send_offer')
                    ->label('Nosūtīt piedāvājumu')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (): bool => ! (auth()->user()?->isPhoto() ?? false)
                        && $this->interestedClients()->isNotEmpty())
                    ->action(function ($livewire): void {
                        $livewire->dispatch(
                            'pdc-open-offer-email',
                            title: 'Nosūtīt piedāvājumu interesentiem',
                            url: route('properties.offers.send', [
                                'propertySlug' => $this->getOwnerRecord()->slug ?? $this->getOwnerRecord()->getKey(),
                            ]),
                            items: $this->offerItems($this->interestedClients()),
                            subject: EmailSetting::offerSubject(),
                            body: EmailSetting::offerBody(),
                        );
                    }),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label('Rediģēt')
                        ->modalHeading('Rediģēt klientu')
                        ->modalSubmitActionLabel('Saglabāt')
                        ->color('gray'),
                    Actions\DetachAction::make()->label('Noņemt')->color('gray'),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('send_offer')
                        ->label('Nosūtīt piedāvājumu')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('gray')
                        ->visible(fn (): bool => ! (auth()->user()?->isPhoto() ?? false))
                        ->action(function (Collection $records, $livewire): void {
                            $interested = $records->filter(
                                fn (Client $record): bool => $record->pivot?->relation === 'interested'
                            )->values();

                            if ($interested->isEmpty()) {
                                Notification::make()
                                    ->title('Nav izvēlēts neviens interesents')
                                    ->body('Piedāvājumu var sūtīt tikai klientiem ar saistību "Interesents".')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $livewire->dispatch(
                                'pdc-open-offer-email',
                                title: 'Nosūtīt piedāvājumu interesentiem',
                                url: route('properties.offers.send', [
                                    'propertySlug' => $this->getOwnerRecord()->slug ?? $this->getOwnerRecord()->getKey(),
                                ]),
                                items: $this->offerItems($interested),
                                subject: EmailSetting::offerSubject(),
                                body: EmailSetting::offerBody(),
                            );
                        }),
                    Actions\DetachBulkAction::make()
                        ->label('Noņemt')
                        ->color('danger'),
                ]),
            ]);
    }

    /** @return Collection<int, Client> */
    private function interestedClients(): Collection
    {
        return $this->getOwnerRecord()
            ->clients()
            ->wherePivot('relation', 'interested')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, Client>  $clients
     * @return array<int, array{id: int, label: string, email: string, readonly: bool}>
     */
    private function offerItems(Collection $clients): array
    {
        return $clients->map(fn (Client $client): array => [
            'id' => $client->id,
            'label' => $client->name,
            'email' => (string) ($client->email ?? ''),
            'readonly' => false,
        ])->values()->all();
    }

    private function offerSentAt(int $clientId): ?Carbon
    {
        $property = $this->getOwnerRecord();

        return $property instanceof CrmProperty
            ? EmailLog::lastPropertyOffer($property->id, $clientId)?->created_at
            : null;
    }
}
