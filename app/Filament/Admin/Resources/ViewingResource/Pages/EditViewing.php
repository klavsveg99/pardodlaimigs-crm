<?php

namespace App\Filament\Admin\Resources\ViewingResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\ViewingResource;
use Filament\Resources\Pages\EditRecord;

class EditViewing extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = ViewingResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt apskati';
    }
}
