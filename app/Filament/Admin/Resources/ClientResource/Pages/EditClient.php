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
                    return null;
                })
                ->icon('heroicon-o-cloud-arrow-up')
                ->color(fn (): string => $this->autosaveState === 'saved' ? 'success' : 'gray')
                ->visible(fn () => $this->autosaveState === 'saved')
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
