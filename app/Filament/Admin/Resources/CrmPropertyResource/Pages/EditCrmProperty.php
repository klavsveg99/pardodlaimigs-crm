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

class EditCrmProperty extends EditRecord
{
    use AttachSellerAction;
    use AutosavesForm;
    use GeneratesAiDescription;
    use SyncsAttachments;

    protected static string $resource = CrmPropertyResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt īpašumu';
    }

    /**
     * "Saglabāt izmaiņas" / "Atcelt" pogas pārceltas uz VISS apakšu — pēc
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
        return Group::make([
            parent::getRelationManagersContentComponent(),
            $this->getFormActionsContentComponent(),
        ]);
    }

    /**
     * Apakšējā "Saglabāt izmaiņas" poga ir ārā no <form> elementa —
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
     * pārdotiem īpašumiem katra izmaiņa jāsaglabā ar "Saglabāt" — citādi
     * katrs taustiņšienis netīšām pārraksta jau publicētos datus.
     */
    protected function canAutosave(): bool
    {
        return $this->record?->status === 'draft';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $wasAutosave = $this->isAutosaveRun;

        $record = parent::handleRecordUpdate($record, $data);

        // AI piezīmes tiek glabātas komponentes stāvoklī (modalā textarea),
        // nevis caur shēmas dehidrāciju — saglabājam tās šeit.
        $aiNotes = $this->data['ai_notes'] ?? null;
        if (is_array($aiNotes)) {
            $aiNotes = array_filter($aiNotes, fn ($v): bool => filled($v));
            $record->update(['ai_notes' => $aiNotes === [] ? null : $aiNotes]);
        }

        // Versija tiek fiksēta tikai pie manuālas saglabāšanas, nevis pie
        // melnraksta autosave — citādi katra taustiņšienis radītu jaunu versiju.
        if (! $wasAutosave && $record->wasChanged('description')) {
            $this->storeDescriptionRevision($record);
        }

        return $record;
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
        return [
            Actions\Action::make('save')
                ->label('Saglabāt izmaiņas')
                ->color('primary')
                ->badge(fn (): ?string => $this->autosaveState === 'error' ? ($this->autosaveError ?: 'Nav izdevies saglabāt') : null)
                ->badgeColor('warning')
                ->keyBindings(['mod+s'])
                ->action(function () {
                    $this->save();
                }),
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
