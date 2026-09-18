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

    /**
     * Property attachments live in two collections: gallery images and
     * separate "Pielikumi" documents.
     */
    protected function attachmentCollections(): array
    {
        return ['attachments' => 'gallery', 'attachments_documents' => 'documents'];
    }

    protected static string $resource = CrmPropertyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Agents only see their own properties, so default the owner to the
        // creator when none is chosen.
        if (blank($data['owner_user_id'] ?? null) && ! auth()->user()?->can('manage')) {
            $data['owner_user_id'] = auth()->id();
        }

        $this->captureAttachments($data);

        return $data;
    }

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

        // AI rezultāts tika ģenerēts pirms ieraksta izveides (Create lapā
        // ieraksts vēl neeksistē) — saglabājam tiklīdz ieraksts ir.
        if ($this->aiResult !== []) {
            $record->update(['ai_result' => $this->aiResult]);
        }

        return $record;
    }
}
