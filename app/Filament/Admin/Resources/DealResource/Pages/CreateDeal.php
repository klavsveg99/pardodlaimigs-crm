<?php

namespace App\Filament\Admin\Resources\DealResource\Pages;

use App\Filament\Admin\Resources\DealResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Resources\Pages\CreateRecord;

class CreateDeal extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = DealResource::class;
}
