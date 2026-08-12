<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Viewing;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayViewings extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    public string $scope = 'mine';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Šodien ieplānotās apskates')
            ->query(fn () => $this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')->label('Laiks')->dateTime('H:i')->alignCenter(),
                Tables\Columns\TextColumn::make('property.title')->label('Īpašums')->limit(40)->placeholder('—'),
                Tables\Columns\TextColumn::make('client.name')->label('Klients'),
                Tables\Columns\TextColumn::make('agent.name')->label('Aģents')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')->badge()
                    ->colors([
                        'info'    => 'scheduled',
                        'success' => 'done',
                        'danger'  => 'cancelled',
                        'warning' => 'no_show',
                    ]),
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

    protected function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Viewing::query()
            ->with(['property', 'client', 'agent'])
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('scheduled_at');

        if ($this->scope === 'mine' && auth()->check()) {
            $query->where('agent_user_id', auth()->id());
        }

        return $query;
    }
}
