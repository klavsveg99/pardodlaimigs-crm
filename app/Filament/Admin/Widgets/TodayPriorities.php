<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Task;
use App\Models\Viewing;
use App\Services\Notices\NoticeCenter;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

/**
 * "Šodien jāizdara" — notikumu saraksts bez fiksētām kolonnām. Katrs
 * ieraksts pats nes sev līdzi datus (nosaukums, lauki, saite), tāpēc
 * uzdevumus, apskates un aizveramos paziņojumus (līdi, novecojuši īpašumi)
 * var rādīt vienā plūsmā. Paziņojumu aizvēršana sinhronizējas ar augšējā
 * labā stūra paziņojumiem (notification_dismissals tabula).
 */
class TodayPriorities extends Widget
{
    protected string $view = 'filament.admin.widgets.today-priorities';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    /** Rāda uzreiz ar lapu, lai paziņojumi ir redzami bez papildu ielādes. */
    protected static bool $isLazy = false;

    /** Kad true, rāda tikai šī lietotāja uzdevumus. */
    public bool $showOnlyMine = false;

    public function toggleOnlyMine(): void
    {
        $this->showOnlyMine = ! $this->showOnlyMine;
    }

    #[On('notices-changed')]
    public function noticesChanged(): void
    {
        // Atkārtots renderējums pēc paziņojuma aizvēršanas.
    }

    public function dismiss(string $key): void
    {
        $user = auth()->user();

        if ($user) {
            app(NoticeCenter::class)->dismiss($user, $key);
        }

        $this->dispatch('notices-changed');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNotifications(): array
    {
        $records = array_merge(
            $this->noticeNotifications(),
            $this->taskNotifications(),
            $this->viewingNotifications(),
        );

        usort($records, function (array $a, array $b): int {
            // Paziņojumi (bez laika) ir pirmie.
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
     * Aizveramie paziņojumi (līdi + novecojuši īpašumi) no NoticeCenter.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function noticeNotifications(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return array_map(fn (array $notice): array => [
            'key' => $notice['key'],
            'type' => $notice['type'],
            'type_color' => $notice['type_color'],
            'icon' => $notice['icon'],
            'urgent' => $notice['urgent'],
            'title' => $notice['title'],
            'url' => $notice['url'],
            'fields' => $notice['fields'],
            'timestamp' => null,
            'dismissable' => true,
        ], app(NoticeCenter::class)->forUser($user));
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
                        ['label' => 'Laiks', 'value' => $task->due_display],
                        $task->assignedTo ? ['label' => 'Aģents', 'value' => $task->assignedTo->name] : null,
                        $task->client ? ['label' => 'Klients', 'value' => $task->client->name] : null,
                    ])),
                    'status' => $overdue ? 'Nokavēts' : 'Plānots',
                    'status_color' => $overdue ? 'danger' : 'gray',
                    'timestamp' => $task->effectiveDueAt(),
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
            // Tikai neizpildītas apskates — pabeigtas/atceltas šodienas
            // apskates nav jādara, tāpēc tās nerādām.
            ->where('status', 'scheduled')
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
                    ['label' => 'Laiks', 'value' => $viewing->scheduled_display],
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
