<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PropertySearchResource\Pages;

use App\Filament\Admin\Resources\PropertySearchResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePropertySearch extends CreateRecord
{
    protected static string $resource = PropertySearchResource::class;

    public function getTitle(): string
    {
        return 'Jauns meklētājs';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['user_id'] ?? null)) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
