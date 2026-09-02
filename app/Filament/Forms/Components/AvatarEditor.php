<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class AvatarEditor extends Field
{
    protected string $view = 'filament.forms.components.avatar-editor';

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
