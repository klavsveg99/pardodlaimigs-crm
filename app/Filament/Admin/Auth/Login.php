<?php

namespace App\Filament\Admin\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Pierakstīties';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Pārdod Laimīgs · CRM';
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->color('primary');
    }
}
