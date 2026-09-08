<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\AttachSellerAction;
use App\Filament\Admin\Resources\Pages\Concerns\AutosavesForm;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCrmProperty extends EditRecord
{
    use AttachSellerAction;
    use AutosavesForm;
    use SyncsAttachments;

    protected static string $resource = CrmPropertyResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt īpašumu';
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
                ->label('Saglabāt')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->keyBindings(['mod+s'])
                ->action(function () {
                    $this->save();
                }),
            Actions\Action::make('autosave_indicator')
                ->label(function (): ?string {
                    if ($this->autosaveState === 'saved' && $this->autosaveAt) {
                        return 'Saglabāts '.$this->autosaveAt;
                    }
                    if ($this->autosaveState === 'error') {
                        return $this->autosaveError ?: 'Neizdevās saglabāt';
                    }

                    return null;
                })
                ->icon(fn (): string => $this->autosaveState === 'error' ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-cloud-arrow-up')
                ->color(fn (): string => match ($this->autosaveState) {
                    'saved' => 'success',
                    'error' => 'warning',
                    default => 'gray',
                })
                ->visible(fn () => in_array($this->autosaveState, ['saved', 'error'], true))
                ->disabled(),
            $this->getAttachSellerAction(),
            $this->getAttachBuyerAction(),
            Actions\Action::make('open_site')
                ->label('Skatīt')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->public_url)
                ->openUrlInNewTab(),
        ];
    }
}
