<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages;

use App\Filament\Admin\Resources\KnowledgeCategoryResource;
use App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages\Concerns\AssignsKnowledgeCourse;
use App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages\Concerns\SyncsKnowledgeCategoryMaterials;
use App\Models\KnowledgeArticle;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class EditKnowledgeCategory extends EditRecord
{
    use AssignsKnowledgeCourse;
    use SyncsKnowledgeCategoryMaterials;

    protected static string $resource = KnowledgeCategoryResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt kursu';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['materials'] = $this->record->articles()
            ->orderBy('knowledge_article_knowledge_category.sort_order')
            ->get()
            ->map(fn (KnowledgeArticle $article): array => ['article_id' => $article->id])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->categoryMaterials = $data['materials'] ?? [];
        unset($data['materials']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncMaterials($this->categoryMaterials);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
            View::make('filament.knowledge.category-assignments')
                ->viewData(fn (): array => [
                    'category' => $this->getRecord(),
                    'assignments' => $this->getRecord()?->courseAssignments()
                        ->with(['assignedTo', 'assignedBy'])
                        ->latest()
                        ->get() ?? collect(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->assignCourseAction(),
        ];
    }
}
