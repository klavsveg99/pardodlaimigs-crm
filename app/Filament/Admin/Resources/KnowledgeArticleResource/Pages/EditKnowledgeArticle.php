<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeArticle extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = KnowledgeArticleResource::class;

    protected function attachmentCollections(): array
    {
        return ['attachments' => 'knowledge'];
    }

    public function getTitle(): string
    {
        return 'Rediģēt materiālu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Skatīt materiālu')->color('gray'),
            Actions\DeleteAction::make()->label('Dzēst materiālu')->color('danger'),
        ];
    }
}
