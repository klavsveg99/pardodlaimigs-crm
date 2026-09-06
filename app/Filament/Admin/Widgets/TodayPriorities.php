<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Task;
use App\Models\Viewing;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class TodayPriorities extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /** When true, the table shows only the current user's tasks (aria). */
    public bool $showOnlyMine = false;

    public function getTableRecordKey(EloquentModel|array $record): string
    {
        return (string) ($record['type'].'-'.$record['id']);
    }

    public function toggleOnlyMine(): void
    {
        $this->showOnlyMine = ! $this->showOnlyMine;
    }

    public function table(Table $table): Table
    {
        $records = $this->collectToday();

        return $table
            ->heading('Šodien jāizdara')
            ->description(now()->locale('lv')->translatedFormat('l, d.m.Y'))
            ->headerActions([
                \Filament\Actions\Action::make('mani_uzdevumi')
                    ->label('Mani uzdevumi')
                    ->icon('heroicon-o-user')
                    ->color(fn (): string => $this->showOnlyMine ? 'primary' : 'gray')
                    ->outlined(fn (): bool => ! $this->showOnlyMine)
                    ->action(fn () => $this->toggleOnlyMine()),
            ])
            ->query(Task::query()->whereRaw('1 = 0'))
            ->records(fn () => $records)
            ->columns([
                Tables\Columns\TextColumn::make('time')
                    ->label('Laiks')
                    ->dateTime('H:i')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Veids')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state),

                Tables\Columns\TextColumn::make('title')
                    ->label('Kas')
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('assigned')
                    ->label('Aģents')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('client')
                    ->label('Klients')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')
                    ->badge(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Šodien nekas nav jāveic')
            ->emptyStateDescription('Nav uzdevumu vai apskatu uz šodienu.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function collectToday(): array
    {
        $records = [];
        $today = now()->toDateString();

        if ($this->showOnlyMine) {
            foreach ($this->myTasksToday() as $record) {
                $records[] = $record;
            }
        } else {
            // Tasks due today or overdue (not completed)
            Task::query()
                ->whereNull('completed_at')
                ->where(function ($q) use ($today) {
                    $q->whereDate('due_at', $today)
                      ->orWhere(function ($q2) {
                          $q2->whereNotNull('due_at')->where('due_at', '<', now());
                      });
                })
                ->with(['assignedTo', 'client'])
                ->get()
                ->each(function (Task $task) use (&$records) {
                    $records[] = [
                        'id' => $task->id,
                        'time' => $task->due_at,
                        'type' => 'Uzdevums',
                        'title' => $task->title,
                        'assigned' => $task->assignedTo?->name,
                        'client' => $task->client?->name,
                        'status' => $task->isOverdue() ? 'Nokavēts' : 'Plānots',
                    ];
                });

            // Viewings today
            Viewing::query()
                ->whereDate('scheduled_at', $today)
                ->with(['agent', 'client', 'property'])
                ->get()
                ->each(function (Viewing $viewing) use (&$records) {
                    $records[] = [
                        'id' => $viewing->id,
                        'time' => $viewing->scheduled_at,
                        'type' => 'Apskate',
                        'title' => $viewing->property?->title ?? 'Īpašums',
                        'assigned' => $viewing->agent?->name,
                        'client' => $viewing->client?->name,
                        'status' => $viewing->status,
                    ];
                });
        }

        usort($records, fn ($a, $b) => ($a['time']?->getTimestamp() ?? 0) <=> ($b['time']?->getTimestamp() ?? 0));

        return array_values($records);
    }

    /**
     * Current user's open tasks due today, formatted as table rows.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function myTasksToday(): array
    {
        $today = now()->toDateString();
        $rows = [];

        Task::query()
            ->whereNull('completed_at')
            ->where('assigned_user_id', auth()->id())
            ->where(function ($q) use ($today) {
                $q->whereDate('due_at', $today)
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('due_at')->where('due_at', '<', now());
                  });
            })
            ->with(['assignedTo', 'client'])
            ->get()
            ->each(function (Task $task) use (&$rows) {
                $rows[] = [
                    'id' => $task->id,
                    'time' => $task->due_at,
                    'type' => 'Uzdevums',
                    'title' => $task->title,
                    'assigned' => $task->assignedTo?->name,
                    'client' => $task->client?->name,
                    'status' => $task->isOverdue() ? 'Nokavēts' : 'Plānots',
                ];
            });

        return $rows;
    }
}
