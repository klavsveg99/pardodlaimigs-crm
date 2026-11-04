<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages;

use App\Filament\Admin\Resources\KnowledgeCategoryResource;
use App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages\Concerns\AssignsKnowledgeCourse;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class EditKnowledgeCategory extends EditRecord
{
    use AssignsKnowledgeCourse;

    protected static string $resource = KnowledgeCategoryResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt kategoriju';
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
