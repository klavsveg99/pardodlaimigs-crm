<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\CrmProperty;
use App\Models\Task;
use App\Models\Viewing;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * "Šodien jāizdara" — notikumu saraksts bez fiksētām kolonnām. Katrs
 * ieraksts pats nes sev līdzi datus (nosaukums, lauki, saite), tāpēc
 * uzdevumus, apskates un īpašumu atgādinājumus var rādīt vienā plūsmā.
 */
class TodayPriorities extends Widget
{
    protected string $view = 'filament.admin.widgets.today-priorities';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    /** Kad true, rāda tikai šī lietotāja uzdevumus. */
    public bool $showOnlyMine = false;

    public function toggleOnlyMine(): void
    {
        $this->showOnlyMine = ! $this->showOnlyMine;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNotifications(): array
    {
        $records = array_merge(
            $this->propertyNotifications(),
            $this->taskNotifications(),
            $this->viewingNotifications(),
        );

        usort($records, function (array $a, array $b): int {
            // Īpašumu atgādinājumi (bez laika) ir pirmie.
            $aTime = $a['timestamp'] instanceof Carbon ? $a['timestamp']->getTimestamp() : -1;
            $bTime = $b['timestamp'] instanceof Carbon ? $b['timestamp']->getTimestamp() : -1;

            return $aTime <=> $bTime;
        });

        return array_values($records);
    }

    public function hasNotifications(): bool
    {
        return $this->getNotifications() !== [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function propertyNotifications(): array
    {
        $user = auth()->user();
        $today = now()->toDateString();

        return CrmProperty::query()
            ->priceStatusStale()
            ->when(! $user?->can('manage') && ! $user?->isPhoto(), fn ($q) => $q->where('owner_user_id', $user?->id))
            ->orderBy('price_status_changed_at')
            ->get()
            ->map(function (CrmProperty $property) use ($today): array {
                $fields = [
                    ['label' => 'Bez izmaiņām', 'value' => $property->daysWithoutPriceStatusChange().' dienas'],
                    ['label' => 'Pārdošanas sākums', 'value' => $property->sale_started_at?->format('d.m.Y') ?? '—'],
                    ['label' => 'Līguma periods', 'value' => $property->partnership_label],
                ];

                return [
                    'key' => 'property-'.$property->id,
                    'type' => 'Īpašums',
                    'type_color' => 'warning',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'urgent' => true,
                    'title' => $property->title,
                    'url' => route('filament.admin.resources.properties.view', $property),
                    'fields' => $fields,
                    'timestamp' => null,
                    'date' => $today,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function taskNotifications(): array
    {
        $today = now()->toDateString();

        return Task::query()
            ->whereNull('completed_at')
            ->when($this->scopeToUser(), fn ($q) => $q->where('assigned_user_id', auth()->id()))
            ->where(function ($q) use ($today): void {
                $q->whereDate('due_at', $today)
                    ->orWhere(function ($q2): void {
                        $q2->whereNotNull('due_at')->where('due_at', '<', now());
                    });
            })
            ->with(['assignedTo', 'client'])
            ->get()
            ->map(function (Task $task): array {
                $overdue = $task->isOverdue();

                return [
                    'key' => 'task-'.$task->id,
                    'type' => 'Uzdevums',
                    'type_color' => $overdue ? 'danger' : 'gray',
                    'icon' => $overdue ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clipboard-document-check',
                    'urgent' => $overdue,
                    'title' => $task->title,
                    'url' => route('filament.admin.resources.tasks.edit', $task),
                    'fields' => array_values(array_filter([
                        ['label' => 'Laiks', 'value' => $task->due_at?->locale('lv')->translatedFormat('d.m.Y H:i')],
                        $task->assignedTo ? ['label' => 'Aģents', 'value' => $task->assignedTo->name] : null,
                        $task->client ? ['label' => 'Klients', 'value' => $task->client->name] : null,
                    ])),
                    'status' => $overdue ? 'Nokavēts' : 'Plānots',
                    'status_color' => $overdue ? 'danger' : 'gray',
                    'timestamp' => $task->due_at,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function viewingNotifications(): array
    {
        $today = now()->toDateString();

        return Viewing::query()
            ->whereDate('scheduled_at', $today)
            ->when($this->scopeToUser(), fn ($q) => $q->where('agent_user_id', auth()->id()))
            ->with(['agent', 'client', 'property'])
            ->get()
            ->map(fn (Viewing $viewing): array => [
                'key' => 'viewing-'.$viewing->id,
                'type' => 'Apskate',
                'type_color' => 'info',
                'icon' => 'heroicon-o-map-pin',
                'urgent' => false,
                'title' => $viewing->property?->title ?? 'Īpašums',
                'url' => route('filament.admin.resources.viewings.edit', $viewing),
                'fields' => array_values(array_filter([
                    ['label' => 'Laiks', 'value' => $viewing->scheduled_at?->locale('lv')->translatedFormat('d.m.Y H:i')],
                    $viewing->agent ? ['label' => 'Aģents', 'value' => $viewing->agent->name] : null,
                    $viewing->client ? ['label' => 'Klients', 'value' => $viewing->client->name] : null,
                ])),
                'status' => [
                    'scheduled' => 'Ieplānota',
                    'completed' => 'Pabeigta',
                    'cancelled' => 'Atcelta',
                ][$viewing->status] ?? $viewing->status,
                'status_color' => 'gray',
                'timestamp' => $viewing->scheduled_at,
            ])
            ->all();
    }

    /** Vai uzdevumu/apskašu sarakstu ierobežot tikai ar šo lietotāju. */
    protected function scopeToUser(): bool
    {
        return $this->showOnlyMine || ! auth()->user()?->can('manage');
    }
}
