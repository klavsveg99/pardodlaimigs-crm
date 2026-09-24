<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FollowUpLeadResource\Pages;

use App\Filament\Admin\Resources\FollowUpLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFollowUpLead extends EditRecord
{
    protected static string $resource = FollowUpLeadResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt Follow Up līdi';
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Dzēst')->color('gray')];
    }
}
