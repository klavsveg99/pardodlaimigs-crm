<?php

namespace App\Filament\Admin\Resources\TaskResource\Pages;

use App\Filament\Admin\Resources\CalendarResource\Pages\CalendarPage as CombinedCalendarPage;
use App\Filament\Admin\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Kalendārs')
                ->icon('heroicon-o-calendar-days')
                ->url(CombinedCalendarPage::getUrl()),
            Actions\CreateAction::make()->label('Jauns uzdevums')->color('gray'),
        ];
    }
}
