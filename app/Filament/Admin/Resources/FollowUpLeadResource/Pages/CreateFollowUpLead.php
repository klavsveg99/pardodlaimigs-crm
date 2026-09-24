<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FollowUpLeadResource\Pages;

use App\Filament\Admin\Resources\FollowUpLeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFollowUpLead extends CreateRecord
{
    protected static string $resource = FollowUpLeadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Aģents redz tikai savus līdus, tāpēc bez norādīta aģenta tas
        // jāpiesaista viņam pašam, citādi ieraksts pazūd no saraksta.
        if (blank($data['owner_user_id'] ?? null) && ! auth()->user()?->can('manage')) {
            $data['owner_user_id'] = auth()->id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
