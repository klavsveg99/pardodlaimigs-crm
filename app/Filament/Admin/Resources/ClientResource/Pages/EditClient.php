<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\AutosavesForm;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    use AutosavesForm;
    use SyncsAttachments;

    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt klientu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Skatīt'),
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
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['source']) && is_string($data['source']) && str_starts_with($data['source'], 'Cits: ')) {
            $data['source_other'] = mb_substr($data['source'], 6);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['source'] ?? null) === 'Cits' && filled($data['source_other'] ?? null)) {
            $data['source'] = 'Cits: ' . $data['source_other'];
        }

        unset($data['source_other']);

        $this->captureAttachments($data);

        return $data;
    }
}
