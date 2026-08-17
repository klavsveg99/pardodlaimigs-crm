<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingTasks extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public string $scope = 'mine';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Nākamie uzdevumi un nokavētie')
            ->query(fn () => $this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Uzdevums')->sortable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('due_at')->label('Līdz')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Kam')->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->sortable()->placeholder('—'),
                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Nokavēts')->boolean()
                    ->getStateUsing(fn ($record) => $record->isOverdue())
                    ->trueColor('danger')->falseColor('gray'),
            ])
            ->headerActions([
                Action::make('scope')
                    ->label(fn () => $this->scope === 'all' ? 'Visi' : 'Mani')
                    ->icon(fn () => $this->scope === 'all' ? 'heroicon-o-users' : 'heroicon-o-user')
                    ->color(fn () => $this->scope === 'all' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->scope = $this->scope === 'all' ? 'mine' : 'all';
                        $this->flushCachedTableRecords();
                    }),
            ])
            ->paginated(false);
    }

    protected function getQuery(): Builder
    {
        $query = Task::query()
            ->whereNull('completed_at')
            ->orderByRaw('CASE WHEN due_at < ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('due_at')
            ->limit(10);

        if ($this->scope === 'mine' && auth()->check()) {
            $query->where('assigned_user_id', auth()->id());
        }

        return $query;
    }
}
