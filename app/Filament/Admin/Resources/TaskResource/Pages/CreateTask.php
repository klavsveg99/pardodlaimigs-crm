<?php

namespace App\Filament\Admin\Resources\TaskResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\TaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Aģents lauku redz tikai administrators; pārējiem uzdevums tiek
        // piesaistīts pašam veidotājam.
        if (blank($data['assigned_user_id'] ?? null) && ! auth()->user()?->can('manage')) {
            $data['assigned_user_id'] = auth()->id();
        }

        return $data;
    }
}
