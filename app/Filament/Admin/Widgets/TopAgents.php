<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\CrmProperty;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;

class TopAgents extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(EloquentModel|array $record): string
    {
        return (string) data_get($record, 'id');
    }

    public function table(Table $table): Table
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $monthLabel = now()->locale('lv')->translatedFormat('F Y');

        // Build aggregated data for current month
        // We use sold_at if present, fallback to updated_at for legacy
        $rows = CrmProperty::query()
            ->selectRaw('
                owner_user_id,
                COUNT(*) as sold_count,
                COALESCE(SUM(commission_eur), 0) as total_commission,
                COALESCE(SUM(final_price_eur), 0) as total_final_price,
                COALESCE(AVG(CASE WHEN final_price_eur > 0 AND commission_eur > 0 THEN commission_eur / final_price_eur * 100 ELSE NULL END), 0) as avg_percent
            ')
            ->where('status', 'sold')
            ->whereNotNull('owner_user_id')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->whereNull('sold_at')
                         ->whereBetween('updated_at', [$start, $end]);
                  });
            })
            ->groupBy('owner_user_id')
            ->orderByDesc('total_commission')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->owner_user_id);
                if (! $user) {
                    return null;
                }
                // Calculate avg percent as total commission / total final price if we want weighted, but we already did AVG per row
                // For display we use the SQL avg_percent, fallback to 0
                $avg = (float) ($row->avg_percent ?? 0);
                // If avg is 0 but we have totals, compute weighted
                if ($avg == 0 && (float) $row->total_final_price > 0) {
                    $avg = (float) $row->total_commission / (float) $row->total_final_price * 100;
                }

                return [
                    'id' => $user->id,
                    'user' => $user,
                    'name' => $user->name,
                    'avatar' => $user->avatar_path,
                    'sold_count' => (int) $row->sold_count,
                    'total_commission' => (float) $row->total_commission,
                    'total_final_price' => (float) $row->total_final_price,
                    'avg_percent' => round($avg, 2),
                ];
            })
            ->filter()
            ->values();

        // Add rank and medal
        $ranked = $rows->map(function ($item, $index) {
            $rank = $index + 1;
            $medal = match ($rank) {
                1 => '🥇',
                2 => '🥈',
                3 => '🥉',
                default => (string) $rank,
            };
            $medalColor = match ($rank) {
                1 => 'warning', // gold
                2 => 'gray',    // silver
                3 => 'warning', // bronze - use warning but could be custom
                default => 'gray',
            };
            $item['rank'] = $rank;
            $item['medal'] = $medal;
            $item['medalColor'] = $medalColor;
            return $item;
        });

        return $table
            ->heading("Top 5 aģenti — {$monthLabel}")
            ->description('Pēc pārdoto īpašumu komisijas šomēnes (kalendāra mēnesis)')
            ->query(
                // Dummy query to satisfy TableWidget; we override records via ->records()
                User::query()->whereRaw('1 = 0')
            )
            ->records(fn () => $ranked)
            ->columns([
                Tables\Columns\TextColumn::make('medal')
                    ->label('#')
                    ->alignCenter()
                    ->width('60px')
                    ->formatStateUsing(fn ($state, $record) => $record['medal'] ?? $record['rank'])
                    ->badge()
                    ->extraAttributes(['style' => 'font-size: 1.1rem;']),

                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(asset('images/no-photo.svg'))
                    ->getStateUsing(fn ($record) => $record['user']?->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($record['user']->avatar_path) : null)
                    ->height(36)
                    ->width(36),

                Tables\Columns\TextColumn::make('name')
                    ->label('Aģents')
                    ->weight('bold')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record['name']),

                Tables\Columns\TextColumn::make('sold_count')
                    ->label('Pārdoti')
                    ->alignCenter()
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record['sold_count'] . ' īpaš.')

                    ->extraCellAttributes(['class' => 'pdc-nowrap']),

                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Komisija mēnesī')
                    ->alignEnd()
                    ->money('EUR')
                    ->getStateUsing(fn ($record) => $record['total_commission'])
                    ->weight('bold')
                    ->color('primary')
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),

                Tables\Columns\TextColumn::make('avg_percent')
                    ->label('Vid. %')
                    ->alignCenter()
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $record['avg_percent'], 2, ',', ' ') . ' %')
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),

                Tables\Columns\TextColumn::make('total_final_price')
                    ->label('Gala cenas kopsumma')
                    ->alignEnd()
                    ->money('EUR')
                    ->getStateUsing(fn ($record) => $record['total_final_price'])
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
            ])
            ->paginated(false)
            ->emptyStateHeading('Šomēnes vēl nav pārdotu īpašumu')
            ->emptyStateDescription('Kad īpašums tiks atzīmēts kā Pārdots ar gala cenu un komisiju, tas parādīsies šeit.')
            ->emptyStateIcon('heroicon-o-trophy');
    }
}
