<?php

namespace App\Filament\Admin\Resources\DealResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\DealResource;
use Filament\Resources\Pages\EditRecord;

class EditDeal extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = DealResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt darījumu';
    }
}