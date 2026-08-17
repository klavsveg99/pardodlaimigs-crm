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
                    ->selectRaw('stage, COUNT(*) as count, COALESCE(SUM(value_eur), 0) as total_eur')
                    ->where('stage', '!=', 'pardots')
                    ->groupBy('stage')
                    ->when(
                        in_array(DB::getDriverName(), ['mysql', 'mariadb']),
                        fn ($query) => $query->orderByRaw('FIELD(stage, "jauns","pirma_tiksanas","noslegta_sadarbiba","foto_video","tirgosana","dokumentu_saskanosana")'),
                        fn ($query) => $query->orderBy('stage')
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->label('Posms')
                    ->badge()
                    ->colors([
                        'info' => ['jauns', 'tirgosana'],
                        'warning' => 'pirma_tiksanas',
                        'primary' => 'noslegta_sadarbiba',
                        'gray' => 'foto_video',
                        'danger' => 'dokumentu_saskanosana',
                    ])
                    ->formatStateUsing(fn ($state) => Deal::STAGES[$state] ?? $state),
                Tables\Columns\TextColumn::make('count')->label('Skaits')->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('total_eur')
                    ->label('Kopā')
                    ->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->money('EUR'),
            ])
            ->paginated(false);
    }
}
