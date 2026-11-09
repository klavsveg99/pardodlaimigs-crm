<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PropertySearchResource\Pages;

use App\Filament\Admin\Resources\PropertySearchResource;
use Filament\Resources\Pages\EditRecord;

class EditPropertySearch extends EditRecord
{
    protected static string $resource = PropertySearchResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt meklētāju';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
