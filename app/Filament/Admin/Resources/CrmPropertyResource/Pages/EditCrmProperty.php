<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\AttachSellerAction;
use App\Filament\Admin\Resources\Pages\Concerns\AutosavesForm;
use App\Filament\Admin\Resources\Pages\Concerns\GeneratesAiDescription;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditCrmProperty extends EditRecord
{
    use AttachSellerAction;
    use AutosavesForm;
    use GeneratesAiDescription;
    use SyncsAttachments {
        // The sold-status hook below overrides the trait's afterSave(), which
        // would otherwise never run - the alias keeps the gallery/documents
        // sync alive on every save.
        afterSave as protected syncAttachmentsAfterSave;
    }

    /**
     * Vai pēdējā saglabāšanā statuss tika mainīts uz "Pārdots" - tad pēc
     * saglabāšanas piedāvājam uzreiz piesaistīt pircēju.
     */
    protected bool $statusChangedToSold = false;

    /**
     * Apraksta versija, kuru lietotājs gatavojas neatgriezeniski dzēst
     * (izmanto apstiprinājuma modālis).
     */
    public ?int $deletingDescriptionRevisionId = null;

    /**
     * Property attachments live in two collections: gallery images and
     * separate "Pielikumi" documents.
     */
    protected function attachmentCollections(): array
    {
        return ['attachments' => 'gallery', 'attachments_documents' => 'documents'];
    }

    protected static string $resource = CrmPropertyResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt īpašumu';
    }

    /**
     * "Saglabāt izmaiņas" / "Atcelt" pogas pārceltas uz VISS apakšu - pēc
     * saistīto ierakstu sadaļām (Piesaistītie klienti), nevis starp formu
     * un Piesaistītie klienti (pēc noklusējuma).
     */
    public function getFormContentComponent(): Component
    {
        if (! $this->hasFormWrapper()) {
            return Group::make([
                EmbeddedSchema::make('form'),
            ]);
        }

        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName());
    }

    public function getRelationManagersContentComponent(): Component
    {
        // Fotogrāfam nav pieejami saistīto klientu pārvaldības bloki -
        // tikai nosaukums un galerija.
        if (auth()->user()?->isPhoto()) {
            return Group::make([
                $this->getFormActionsContentComponent(),
            ]);
        }

        return Group::make([
            parent::getRelationManagersContentComponent(),
            $this->getFormActionsContentComponent(),
        ]);
    }

    /**
     * Apakšējā "Saglabāt izmaiņas" poga ir ārā no <form> elementa -
     * HTML submit vairs nedarbojas, tāpēc saglabāšana notiek tieši ar
     * Livewire save() izsaukumu (kā augšējai "Saglabāt" pogai).
     */
    protected function getSaveFormAction(): Actions\Action
    {
        return Actions\Action::make('save')
            ->label('Saglabāt izmaiņas')
            ->color('primary')
            ->action($this->getSubmitFormLivewireMethodName());
    }

    /**
     * Autosave tikai melnrakstiem ("jauniem" īpašumiem). Publicētiem un
     * pārdotiem īpašumiem katra izmaiņa jāsaglabā ar "Saglabāt" - citādi
     * katrs taustiņšienis netīšām pārraksta jau publicētos datus.
     */
    protected function canAutosave(): bool
    {
        return $this->record?->status === 'draft';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Pārdotu īpašumu nevar saglabāt bez piesaistīta pārdevēja.
        if (($data['status'] ?? null) === 'sold'
            && $record instanceof CrmProperty
            && ! $record->clients()->wherePivot('relation', 'seller')->exists()) {
            throw ValidationException::withMessages([
                'data.status' => 'Īpašumu nevar saglabāt kā "Pārdots" bez piesaistīta pārdevēja. Vispirms piesaisti pārdevēju.',
            ]);
        }

        $wasAutosave = $this->isAutosaveRun;
        $previousStatus = $record->status;

        $record = parent::handleRecordUpdate($record, $data);

        $this->statusChangedToSold = $previousStatus !== 'sold' && $record->status === 'sold';

        // AI piezīmes tiek glabātas komponentes stāvoklī (modalā textarea),
        // nevis caur shēmas dehidrāciju - saglabājam tās šeit.
        $aiNotes = $this->data['ai_notes'] ?? null;
        if (is_array($aiNotes)) {
            $aiNotes = array_filter($aiNotes, fn ($v): bool => filled($v));
            $record->update(['ai_notes' => $aiNotes === [] ? null : $aiNotes]);
        }

        // Versija tiek fiksēta tikai pie manuālas saglabāšanas, nevis pie
        // melnraksta autosave - citādi katra taustiņšienis radītu jaunu versiju.
        // Tukšu aprakstu arhīvā nefiksējam: tāda versija tikai aizņem vietu.
        if (! $wasAutosave && $record->wasChanged('description') && ! $this->descriptionIsEmpty($record->description)) {
            $this->storeDescriptionRevision($record);
        }

        return $record;
    }

    /**
     * Kad īpašums saglabāts ar statusu "Pārdots", uzreiz piedāvājam
     * piesaistīt pircēju, atverot esošo "Pievienot klientu" modāli.
     */
    protected function afterSave(): void
    {
        // Gallery/documents (attachment rows) are synced by the trait; the
        // sold-status prompt below must not skip it.
        $this->syncAttachmentsAfterSave();

        $changedToSold = $this->statusChangedToSold && ! $this->isAutosaveRun;
        $this->statusChangedToSold = false;

        if (! $changedToSold) {
            return;
        }

        $record = $this->getRecord();

        if (! $record instanceof CrmProperty) {
            return;
        }

        // Svinību animācija (konfeti + "Īpašums pārdots") abos ceļos:
        // gan šeit, gan ātrajā darbībā "Pārdots".
        $this->dispatch('pdc-property-sold', title: $record->title);

        if ($record->clients()->wherePivot('relation', 'buyer')->exists()) {
            return;
        }

        $this->mountAction('attach_buyer');
    }

    private function storeDescriptionRevision(CrmProperty $record): void
    {
        $record->descriptionRevisions()->create([
            'user_id' => auth()->id(),
            'description' => $record->description,
        ]);

        // Paturam pēdējās 30 versijas.
        $keepIds = $record->descriptionRevisions()
            ->orderByDesc('id')
            ->limit(30)
            ->pluck('id');

        $record->descriptionRevisions()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * Vai apraksts pēc būtības ir tukšs (tikai tukšas HTML atzīmes, atstarpes
     * vai neredzamās rakstzīmes). Attēlu/video saturošu aprakstu par tukšu
     * neuzskatām.
     */
    private function descriptionIsEmpty(?string $description): bool
    {
        $description = (string) $description;

        if (preg_match('/<(img|iframe|video|audio)\b/i', $description)) {
            return false;
        }

        $text = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00A0}", "\u{200B}", "\u{FEFF}"], '', $text);

        return trim($text) === '';
    }

    public function confirmDescriptionRevisionDeletion(int $revisionId): void
    {
        $revision = $this->getRecord()
            ?->descriptionRevisions()
            ->whereKey($revisionId)
            ->first();

        if (! $revision) {
            Notification::make()->title('Apraksta versija nav atrasta')->danger()->send();

            return;
        }

        $this->deletingDescriptionRevisionId = $revision->id;
        $this->mountAction('deleteDescriptionRevision');
    }

    protected function deleteDescriptionRevisionAction(): Actions\Action
    {
        return Actions\Action::make('deleteDescriptionRevision')
            ->label('Dzēst versiju')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->modalHeading('Dzēst apraksta versiju neatgriezeniski?')
            ->modalDescription('Šī apraksta versija tiks neatgriezeniski dzēsta un to vairs nevarēs atjaunot.')
            ->modalSubmitActionLabel('Dzēst neatgriezeniski')
            ->action(function (): void {
                $revisionId = $this->deletingDescriptionRevisionId;
                $this->deletingDescriptionRevisionId = null;

                $revision = $revisionId
                    ? $this->getRecord()?->descriptionRevisions()->whereKey($revisionId)->first()
                    : null;

                if (! $revision) {
                    Notification::make()->title('Apraksta versija nav atrasta')->danger()->send();

                    return;
                }

                $revision->delete();

                Notification::make()->title('Apraksta versija dzēsta')->success()->send();
            });
    }

    public function revertDescription(int $revisionId): void
    {
        $revision = $this->getRecord()
            ->descriptionRevisions()
            ->whereKey($revisionId)
            ->first();

        if (! $revision) {
            return;
        }

        $this->data['description'] = (string) $revision->description;

        Notification::make()
            ->title('Apraksts atjaunots no saglabātās versijas')
            ->body('Lai pabeigtu, nospied "Saglabāt".')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $save = Actions\Action::make('save')
            ->label('Saglabāt izmaiņas')
            ->color('primary')
            ->badge(fn (): ?string => $this->autosaveState === 'error' ? ($this->autosaveError ?: 'Nav izdevies saglabāt') : null)
            ->badgeColor('warning')
            ->keyBindings(['mod+s'])
            ->action(function () {
                $this->save();
            });

        // Fotogrāfs drīkst saglabāt tikai nosaukumu un galeriju.
        if (auth()->user()?->isPhoto()) {
            return [$save];
        }

        return [
            $save,
            $this->getAttachSellerAction(),
            $this->getAttachBuyerAction(),
            Actions\Action::make('open_site')
                ->label('Atvērt mājaslapā')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->public_url)
                ->openUrlInNewTab(),
            Actions\Action::make('restore_property')
                ->label('Atjaunot')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => $this->record?->status === 'deleted')
                ->requiresConfirmation()
                ->modalHeading('Atjaunot īpašumu?')
                ->modalDescription('Īpašums atgriezīsies kā melnraksts un būs redzams aktīvo īpašumu sarakstā.')
                ->modalSubmitActionLabel('Atjaunot')
                ->action(function (): void {
                    $this->record->update(['status' => 'draft']);
                    Notification::make()
                        ->title('Īpašums atjaunots')
                        ->success()
                        ->send();
                }),
        ];
    }
}
