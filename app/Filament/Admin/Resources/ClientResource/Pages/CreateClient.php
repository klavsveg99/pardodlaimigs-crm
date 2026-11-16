<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'Jauns klients';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = Client::normalizeSourceValue(
            $data['source'] ?? null,
            $data['source_other'] ?? null,
        );

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
