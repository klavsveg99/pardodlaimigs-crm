<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\IzpilditajsResource\Pages;

use App\Filament\Admin\Resources\IzpilditajsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIzpilditajs extends ListRecords
{
    protected static string $resource = IzpilditajsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Jauns izpildītājs')->color('gray'),
        ];
    }
}