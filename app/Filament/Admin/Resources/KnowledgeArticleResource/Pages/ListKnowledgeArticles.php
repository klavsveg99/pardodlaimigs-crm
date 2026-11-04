<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Livewire\KnowledgeCourses;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ListKnowledgeArticles extends ListRecords
{
    protected static string $resource = KnowledgeArticleResource::class;

    public function getTitle(): string
    {
        return 'Zināšanu bāze';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            Livewire::make(KnowledgeCourses::class)
                ->visible(fn (): bool => ! (auth()->user()?->can('manage') ?? false)
                    && ! (auth()->user()?->isPhoto() ?? false)),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
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
