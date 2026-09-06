<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['source'] ?? null) === 'Cits' && filled($data['source_other'] ?? null)) {
            $data['source'] = 'Cits: ' . $data['source_other'];
        }

        unset($data['source_other']);

        $this->captureAttachments($data);

        return $data;
    }
}
