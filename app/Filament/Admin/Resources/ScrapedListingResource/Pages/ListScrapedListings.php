<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ScrapedListingResource\Pages;

use App\Filament\Admin\Resources\ScrapedListingResource;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListScrapedListings extends ListRecords
{
    protected static string $resource = ScrapedListingResource::class;

    public function getTitle(): string
    {
        return 'Atrastie objekti';
    }

    /** Refresh the "Piedāvājums" date after the popup sends an offer. */
    #[On('pdc-offer-sent')]
    public function offerSent(): void
    {
        // Re-render is the point.
    }
}
