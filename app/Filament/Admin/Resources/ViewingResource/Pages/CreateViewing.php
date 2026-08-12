<?php

namespace App\Filament\Admin\Resources\ViewingResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\ViewingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViewing extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = ViewingResource::class;
}
