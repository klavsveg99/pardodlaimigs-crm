<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\KnowledgeArticleResource;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Fotogrāfa un studenta loma neredz pārskata paneli - tie uzreiz tiek
 * novirzīti uz vienīgo sadaļu, kas viņiem pieejama.
 */
class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->isPhoto() ?? false)
            && ! (auth()->user()?->isStudent() ?? false);
    }

    public function mount(): void
    {
        if (auth()->user()?->isStudent()) {
            $this->redirect(KnowledgeArticleResource::getUrl('index'));

            return;
        }

        if (auth()->user()?->isPhoto()) {
            $this->redirect(CrmPropertyResource::getUrl('index'));
        }
    }
}
