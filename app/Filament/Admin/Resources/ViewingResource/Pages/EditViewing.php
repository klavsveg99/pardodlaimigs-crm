<?php

namespace App\Filament\Admin\Resources\ViewingResource\Pages;

use App\Filament\Admin\Resources\CalendarResource\Pages\CalendarPage;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\ViewingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditViewing extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = ViewingResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt apskati';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Kalendārs')
                ->icon('heroicon-o-calendar-days')
                ->url(CalendarPage::getUrl())
                ->color('gray'),
            Actions\DeleteAction::make()
                ->label('Dzēst apskati')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Dzēst apskati?')
                ->successRedirectUrl(ViewingResource::getUrl('index')),
        ];
    }
}
