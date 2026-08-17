<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmProperty extends CreateRecord
{
    use SyncsAttachments;

    protected static string $resource = CrmPropertyResource::class;
}
