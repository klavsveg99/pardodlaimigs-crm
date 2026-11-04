<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages;

use App\Filament\Admin\Resources\KnowledgeCategoryResource;
use App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages\Concerns\SyncsKnowledgeCategoryMaterials;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeCategory extends CreateRecord
{
    use SyncsKnowledgeCategoryMaterials;

    protected static string $resource = KnowledgeCategoryResource::class;

    public function getTitle(): string
    {
        return 'Jauns kurss';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->categoryMaterials = $data['materials'] ?? [];
        unset($data['materials']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncMaterials($this->categoryMaterials);
    }
}
