<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Deal;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;

class DealsByStage extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(EloquentModel|array $record): string
    {
        return (string) data_get($record, 'stage');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Darījumu pašreizējais stāvoklis')
            ->query(
                Deal::query()
                    ->selectRaw('stage, COUNT(*) as count, COALESCE(SUM(value_cents), 0) as total_cents')
                    ->whereNotIn('stage', ['closed_won', 'closed_lost'])
                    ->groupBy('stage')
                    ->when(
                        in_array(DB::getDriverName(), ['mysql', 'mariadb']),
                        fn ($query) => $query->orderByRaw('FIELD(stage, "lead","viewing_scheduled","offer","reserved")'),
                        fn ($query) => $query->orderBy('stage')
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->label('Posms')
                    ->badge()
                    ->colors([
                        'gray'    => 'lead',
                        'info'    => 'viewing_scheduled',
                        'warning' => 'offer',
                        'primary' => 'reserved',
                    ])
                    ->formatStateUsing(fn ($state) => Deal::STAGES[$state] ?? $state),
                Tables\Columns\TextColumn::make('count')->label('Skaits'),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Kopā')
                    ->formatStateUsing(fn ($state) => number_format(((int) $state) / 100, 0, '.', ' ') . ' €'),
            ])
            ->paginated(false);
    }
}
