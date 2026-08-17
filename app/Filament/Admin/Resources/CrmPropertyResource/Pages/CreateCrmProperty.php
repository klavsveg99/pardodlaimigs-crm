<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmProperty extends CreateRecord
{
    protected static string $resource = CrmPropertyResource::class;
}
