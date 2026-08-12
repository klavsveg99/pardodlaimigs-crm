<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Calendar\CalendarEvents;
use Filament\Widgets\Widget;

class CalendarViewings extends Widget
{
    protected string $view = 'filament.admin.widgets.calendar-viewings';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $calendarEvents = app(CalendarEvents::class);

        return [
            'eventsJson' => $calendarEvents->all()->toJson(),
        ];
    }
}
