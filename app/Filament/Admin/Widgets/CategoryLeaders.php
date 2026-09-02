<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\Deal;
use App\Models\Viewing;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class CategoryLeaders extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(EloquentModel|array $record): string
    {
        return (string) data_get($record, 'category');
    }

    public function table(Table $table): Table
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $leaders = $this->computeLeaders($start, $end);

        return $table
            ->heading('Kategoriju līderi šomēnes')
            ->description('Par katru kategoriju parādīts uzvarētājs un tā vērtība')
            ->query(
                Client::query()->whereRaw('1 = 0')
            )
            ->records(fn () => $leaders)
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorija')
                    ->wrap()
                    ->width('220px')
                    ->weight('bold'),
                Tables\Columns\ImageColumn::make('leader_avatar')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(asset('images/no-photo.svg'))
                    ->getStateUsing(fn ($record) => $record['leader_avatar'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($record['leader_avatar']) : null)
                    ->height(36)
                    ->width(36),
                Tables\Columns\TextColumn::make('leader_name')
                    ->label('Uzvarētājs')
                    ->wrap()
                    ->width('160px'),
                Tables\Columns\TextColumn::make('leader_value')
                    ->label('Vērtība')
                    ->alignEnd()
                    ->wrap()
                    ->width('120px')
                    ->formatStateUsing(function ($state, $record) {
                        $value = $record['leader_value'];
                        if (is_numeric($value)) {
                            if (str_contains($record['category'], 'Ātrākais')) {
                                return $value . ' dienas';
                            }
                            return number_format($value, 0, ',', ' ');
                        }
                        return $value;
                    }),
            ])
            ->paginated(false);
    }

    protected function computeLeaders($start, $end): array
    {
        $leaders = [];

        $newClients = Client::whereBetween('created_at', [$start, $end])
            ->whereNotNull('owner_user_id')
            ->get(['owner_user_id'])
            ->groupBy('owner_user_id')
            ->map->count()
            ->sortDesc();

        if ($newClients->count()) {
            $userId = $newClients->keys()->first();
            $user = \App\Models\User::find($userId);
            $leaders[] = [
                'category' => 'Jauni klienti (šomēnes)',
                'leader_name' => $user?->name ?? 'Nav dati',
                'leader_value' => $newClients->first(),
                'leader_avatar' => $user?->avatar_path,
            ];
        }

        $viewingLeader = Viewing::whereBetween('scheduled_at', [$start, $end])
            ->whereNotNull('agent_user_id')
            ->get(['agent_user_id'])
            ->groupBy('agent_user_id')
            ->map->count()
            ->sortDesc();

        if ($viewingLeader->count()) {
            $userId = $viewingLeader->keys()->first();
            $user = \App\Models\User::find($userId);
            $leaders[] = [
                'category' => 'Organizētie apskati (šomēnes)',
                'leader_name' => $user?->name ?? 'Nav dati',
                'leader_value' => $viewingLeader->first(),
                'leader_avatar' => $user?->avatar_path,
            ];
        }

        $dealLeader = Deal::whereNotNull('closed_at')
            ->whereBetween('closed_at', [$start, $end])
            ->whereNotNull('owner_user_id')
            ->get(['owner_user_id'])
            ->groupBy('owner_user_id')
            ->map->count()
            ->sortDesc();

        if ($dealLeader->count()) {
            $userId = $dealLeader->keys()->first();
            $user = \App\Models\User::find($userId);
            $leaders[] = [
                'category' => 'Noslēgtie darījumi (šomēnes)',
                'leader_name' => $user?->name ?? 'Nav dati',
                'leader_value' => $dealLeader->first(),
                'leader_avatar' => $user?->avatar_path,
            ];
        }

        $fastestDeals = Deal::whereNotNull('closed_at')
            ->whereNotNull('created_at')
            ->whereBetween('closed_at', [$start, $end])
            ->whereNotNull('owner_user_id')
            ->get(['owner_user_id', 'created_at', 'closed_at']);

        $fastest = null;
        foreach ($fastestDeals as $deal) {
            $days = $deal->created_at->diffInDays($deal->closed_at);
            if ($fastest === null || $days < $fastest['days']) {
                $fastest = ['user_id' => $deal->owner_user_id, 'days' => $days];
            }
        }

        if ($fastest) {
            $user = \App\Models\User::find($fastest['user_id']);
            $leaders[] = [
                'category' => 'Ātrākais darījums (dienas, šomēnes)',
                'leader_name' => $user?->name ?? 'Nav dati',
                'leader_value' => $fastest['days'],
                'leader_avatar' => $user?->avatar_path,
            ];
        }

        if (empty($leaders)) {
            $leaders[] = [
                'category' => 'Nav dati šomēnes',
                'leader_name' => '',
                'leader_value' => '',
                'leader_avatar' => null,
            ];
        }

        return $leaders;
    }
}
