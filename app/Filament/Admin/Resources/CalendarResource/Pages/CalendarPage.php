<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CalendarResource\Pages;

use App\Filament\Admin\Resources\CalendarResource;
use App\Services\Calendar\CalendarEvents;
use Filament\Resources\Pages\Page;

class CalendarPage extends Page
{
    protected static string $resource = CalendarResource::class;

    protected string $view = 'filament.admin.resources.calendar-resource.pages.calendar';

    protected static ?string $title = 'Kalendārs';

    public ?int $agentFilter = null;

    public function mount(): void
    {
        $this->agentFilter = request()->integer('agentFilter') ?: null;
    }

    public function updatedAgentFilter(): void
    {
        $this->dispatch('calendar-agent-changed', agentId: $this->agentFilter);
    }

    protected function getViewData(): array
    {
        $calendarEvents = app(CalendarEvents::class);

        return [
            'eventsJson' => $calendarEvents->all()->toJson(),
            'agentOptions' => $calendarEvents->agentOptions(),
        ];
    }
}
