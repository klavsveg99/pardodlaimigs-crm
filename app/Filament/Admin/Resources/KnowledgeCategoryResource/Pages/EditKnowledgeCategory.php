<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages;

use App\Filament\Admin\Resources\KnowledgeCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeCategory extends EditRecord
{
    protected static string $resource = KnowledgeCategoryResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt kategoriju';
    }
}
