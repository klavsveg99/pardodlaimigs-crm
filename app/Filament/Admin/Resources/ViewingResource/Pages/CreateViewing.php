<?php

namespace App\Filament\Admin\Resources\ViewingResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\ViewingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViewing extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = ViewingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Agents only see their own viewings, so default the agent to the
        // creator when none is chosen.
        if (blank($data['agent_user_id'] ?? null) && ! auth()->user()?->can('manage')) {
            $data['agent_user_id'] = auth()->id();
        }

        $this->captureAttachments($data);

        return $data;
    }
}
