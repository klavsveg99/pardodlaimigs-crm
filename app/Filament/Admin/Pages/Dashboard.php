<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Fotogrāfa loma neredz pārskata paneli — uzreiz tiek novirzīta uz īpašumu
 * sarakstu, jo pieejamas ir tikai īpašumu darbības.
 */
class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->isPhoto() ?? false);
    }

    public function mount(): void
    {
        if (auth()->user()?->isPhoto()) {
            $this->redirect(CrmPropertyResource::getUrl('index'));
        }
    }
}
