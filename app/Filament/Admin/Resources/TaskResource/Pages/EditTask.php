<?php

namespace App\Filament\Admin\Resources\TaskResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\TaskResource;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = TaskResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt uzdevumu';
    }
}
