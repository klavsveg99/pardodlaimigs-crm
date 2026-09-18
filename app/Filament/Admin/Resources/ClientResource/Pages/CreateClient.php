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

        // Agents only see their own clients, so a client they create must be
        // owned by them; otherwise it vanishes from their list (and the
        // post-create redirect to the view page 404s).
        if (blank($data['owner_user_id'] ?? null) && ! auth()->user()?->can('manage')) {
            $data['owner_user_id'] = auth()->id();
        }

        $this->captureAttachments($data);

        return $data;
    }
}
