<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = ClientResource::class;
}
