<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\AutosavesForm;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Models\Client;
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
            Actions\Action::make('saglabat')
                ->label('Saglabāt izmaiņas')
                ->color(fn (): string => $this->autosaveState === 'error' ? 'warning' : 'primary')
                ->badge(fn (): ?string => $this->autosaveState === 'error' ? ($this->autosaveError ?: 'Nav izdevies saglabāt') : null)
                ->badgeColor('warning')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save()),
            // Registered so the attachment rows' "Nosūtīt" button can mount
            // it via pdc-attachment-send; kept hidden from the header bar.
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        [$source, $other] = Client::sourceForForm(
            is_string($data['source'] ?? null) ? $data['source'] : null,
        );

        $data['source'] = $source;

        if (filled($other)) {
            $data['source_other'] = $other;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['source'] = Client::normalizeSourceValue(
            $data['source'] ?? null,
            $data['source_other'] ?? null,
        );

        unset($data['source_other']);

        $this->captureAttachments($data);

        return $data;
    }
}
