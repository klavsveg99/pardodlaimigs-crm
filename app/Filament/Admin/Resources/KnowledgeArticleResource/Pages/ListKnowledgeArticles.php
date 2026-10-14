<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeArticles extends ListRecords
{
    protected static string $resource = KnowledgeArticleResource::class;

    public function getTitle(): string
    {
        return 'Zināšanu bāze';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Jauns materiāls')
                ->color('gray')
                ->visible(fn (): bool => KnowledgeArticleResource::canCreate()),
        ];
    }
}
