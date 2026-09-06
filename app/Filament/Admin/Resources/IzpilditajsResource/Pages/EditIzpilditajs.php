<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\IzpilditajsResource\Pages;

use App\Filament\Admin\Resources\IzpilditajsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIzpilditajs extends EditRecord
{
    protected static string $resource = IzpilditajsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Skatīt'),
            Actions\DeleteAction::make()->label('Dzēst'),
        ];
    }
}