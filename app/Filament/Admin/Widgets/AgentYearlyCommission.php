<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\CrmProperty;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class AgentYearlyCommission extends BaseWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(EloquentModel|array $record): string
    {
        return (string) data_get($record, 'user_id');
    }

    public function table(Table $table): Table
    {
        $start = now()->startOfYear();
        $end = now()->endOfYear();
        $yearLabel = now()->year;

        $rows = $this->compute($start, $end);

        return $table
            ->heading("Aģentu komisijas šogad — {$yearLabel}")
            ->description('Pēc pārdoto īpašumu komisijas (ārējie līdi: 20% no komisijas)')
            ->query(User::query()->whereRaw('1 = 0'))
            ->records(fn () => $rows)
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/no-photo.svg'))
                    ->getStateUsing(fn ($record) => $record['avatar'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($record['avatar']) : null)
                    ->height(36)
                    ->width(36),

                Tables\Columns\TextColumn::make('name')
                    ->label('Aģents')
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('sold_count')
                    ->label('Pārdoti (gads)')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state, $record) => $record['sold_count'].' īpaš.'),

                Tables\Columns\TextColumn::make('year_commission')
                    ->label('Komisija (gads)')
                    ->alignEnd()
                    ->money('EUR')
                    ->getStateUsing(fn ($record) => $record['year_commission'])
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('avg_percent')
                    ->label('Vid. %')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state, $record) => $record['avg_percent'] !== null
                        ? number_format((float) $record['avg_percent'], 2, ',', ' ').' %'
                        : '—'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Šogad vēl nav pārdotu īpašumu')
            ->emptyStateDescription('Kad īpašums tiks atzīmēts kā Pārdots ar gala cenu un komisiju, tas parādīsies šeit.')
            ->emptyStateIcon('heroicon-o-currency-euro');
    }

    protected function compute($start, $end): array
    {
        $properties = CrmProperty::query()
            ->where('status', 'sold')
            ->whereNotNull('owner_user_id')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                    ->orWhere(fn ($q2) => $q2
                        ->whereNull('sold_at')
                        ->whereBetween('updated_at', [$start, $end]));
            })
            ->get(['owner_user_id', 'sold_at', 'updated_at', 'commission_eur', 'final_price_eur', 'lead_source']);

        $byUser = [];
        foreach ($properties as $property) {
            $at = $property->sold_at ?? $property->updated_at;
            if (! $at) {
                continue;
            }
            $userId = $property->owner_user_id;
            if (! isset($byUser[$userId])) {
                $byUser[$userId] = ['count' => 0, 'commission' => 0.0, 'final_price' => 0.0];
            }
            $c = (float) $property->commission_eur;
            if ($property->lead_source === 'external') {
                $c = $c * 0.2;
            }
            $byUser[$userId]['count']++;
            $byUser[$userId]['commission'] += $c;
            $byUser[$userId]['final_price'] += (float) $property->final_price_eur;
        }

        $rows = collect($byUser)
            ->map(function ($data, $userId) {
                $user = User::find($userId);
                $avg = null;
                if ((float) $data['final_price'] > 0 && (float) $data['commission'] > 0) {
                    $avg = round((float) $data['commission'] / (float) $data['final_price'] * 100, 2);
                }

                return [
                    'user_id' => (int) $userId,
                    'name' => $user?->name ?? 'Nav dati',
                    'avatar' => $user?->avatar_path,
                    'sold_count' => $data['count'],
                    'year_commission' => round($data['commission'], 2),
                    'avg_percent' => $avg,
                ];
            })
            ->sortByDesc('year_commission')
            ->values()
            ->all();

        return $rows;
    }
}
