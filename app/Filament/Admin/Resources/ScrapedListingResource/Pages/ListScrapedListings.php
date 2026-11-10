<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ScrapedListingResource\Pages;

use App\Filament\Admin\Resources\ScrapedListingResource;
use Filament\Resources\Pages\ListRecords;

class ListScrapedListings extends ListRecords
{
    protected static string $resource = ScrapedListingResource::class;

    public function getTitle(): string
    {
        return 'Atrastie objekti';
    }

}
