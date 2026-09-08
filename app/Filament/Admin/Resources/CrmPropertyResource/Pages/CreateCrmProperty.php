<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\GeneratesAiDescription;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCrmProperty extends CreateRecord
{
    use GeneratesAiDescription;
    use SyncsAttachments;

    protected static string $resource = CrmPropertyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        // AI piezīmes (modalā textarea) glabājas komponentes stāvoklī, nevis
        // dehidrētājos datos — pieliekam tās pēc ieraksta izveides.
        $aiNotes = $this->data['ai_notes'] ?? null;
        if (is_array($aiNotes)) {
            $aiNotes = array_filter($aiNotes, fn ($v): bool => filled($v));
            $record->update(['ai_notes' => $aiNotes === [] ? null : $aiNotes]);
        }

        return $record;
    }
}
