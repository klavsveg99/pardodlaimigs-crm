<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\IzpilditajsResource\Pages;

use App\Filament\Admin\Resources\IzpilditajsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIzpilditajs extends ViewRecord
{
    protected static string $resource = IzpilditajsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Rediģēt'),
        ];
    }
}