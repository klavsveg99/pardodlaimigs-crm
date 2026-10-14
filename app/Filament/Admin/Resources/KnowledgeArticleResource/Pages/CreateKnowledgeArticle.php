<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeArticle extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = KnowledgeArticleResource::class;

    protected function attachmentCollections(): array
    {
        return ['attachments' => 'knowledge'];
    }

    public function getTitle(): string
    {
        return 'Jauns materiāls';
    }
}
