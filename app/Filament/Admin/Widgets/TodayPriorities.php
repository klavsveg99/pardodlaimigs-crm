<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Deal;
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

    public function getTableRecordKey(EloquentModel|array $record): string
    {
        return (string) ($record['type'].'-'.$record['id']);
    }

    public function table(Table $table): Table
    {
        $records = $this->collectToday();

        return $table
            ->heading('Šodien jāizdara')
            ->description(now()->locale('lv')->translatedFormat('l, d.m.Y'))
            ->query(Deal::query()->whereRaw('1 = 0'))
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
                    ->formatStateUsing(fn ($state) => $state)
                    ->colors([
                        'warning' => 'Uzdevums',
                        'info' => 'Apskate',
                        'primary' => 'Darījums',
                    ]),

                Tables\Columns\TextColumn::make('title')
                    ->label('Kas')
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('assigned')
                    ->label('Kam')
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
            ->emptyStateDescription('Nav uzdevumu, apskatu vai izpildāmu darījumu uz šodienu.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function collectToday(): array
    {
        $records = [];
        $today = now()->toDateString();

        // Tasks due today (not completed)
        Task::query()
            ->whereNull('completed_at')
            ->whereDate('due_at', $today)
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

        // Deals expected to close today
        Deal::query()
            ->whereDate('expected_close_date', $today)
            ->where('stage', '!=', 'pardots')
            ->whereNull('closed_at')
            ->with(['owner', 'client'])
            ->get()
            ->each(function (Deal $deal) use (&$records) {
                $records[] = [
                    'id' => $deal->id,
                    'time' => $deal->expected_close_date,
                    'type' => 'Darījums',
                    'title' => $deal->title,
                    'assigned' => $deal->owner?->name,
                    'client' => $deal->client?->name,
                    'status' => $deal->getStageLabelAttribute(),
                ];
            });

        usort($records, fn ($a, $b) => ($a['time']?->getTimestamp() ?? 0) <=> ($b['time']?->getTimestamp() ?? 0));

        return array_values($records);
    }
}
