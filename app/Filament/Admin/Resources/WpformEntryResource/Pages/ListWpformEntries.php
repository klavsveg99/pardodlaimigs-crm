<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\WpformEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListWpformEntries extends ListRecords
{
    protected static string $resource = WpformEntryResource::class;
}
