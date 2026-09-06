<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\CrmProperty;
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
        $empty = fn () => ['leader_name' => '—', 'leader_value' => '—', 'leader_avatar' => null];

        // Jauni klienti
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
                'category' => 'Jauni klienti',
                'leader_name' => $user?->name ?? '—',
                'leader_value' => $newClients->first(),
                'leader_avatar' => $user?->avatar_path,
            ];
        } else {
            $leaders[] = array_merge(['category' => 'Jauni klienti'], $empty());
        }

        // Organizētie apskati
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
                'category' => 'Organizētas apskates',
                'leader_name' => $user?->name ?? '—',
                'leader_value' => $viewingLeader->first(),
                'leader_avatar' => $user?->avatar_path,
            ];
        } else {
            $leaders[] = array_merge(['category' => 'Organizētas apskates'], $empty());
        }

        // Pārdoti īpašumi
        $soldLeader = CrmProperty::query()
            ->where('status', 'sold')
            ->whereNotNull('owner_user_id')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->whereNull('sold_at')->whereBetween('updated_at', [$start, $end]);
                  });
            })
            ->get(['owner_user_id'])
            ->groupBy('owner_user_id')
            ->map->count()
            ->sortDesc();

        if ($soldLeader->count()) {
            $userId = $soldLeader->keys()->first();
            $user = \App\Models\User::find($userId);
            $leaders[] = [
                'category' => 'Pārdoti īpašumi',
                'leader_name' => $user?->name ?? '—',
                'leader_value' => $soldLeader->first(),
                'leader_avatar' => $user?->avatar_path,
            ];
        } else {
            $leaders[] = array_merge(['category' => 'Pārdoti īpašumi'], $empty());
        }

        // Ātrākais pārdošanas cikls
        $soldProperties = CrmProperty::query()
            ->where('status', 'sold')
            ->whereNotNull('owner_user_id')
            ->whereNotNull('created_at')
            ->whereNotNull('sold_at')
            ->whereBetween('sold_at', [$start, $end])
            ->get(['owner_user_id', 'created_at', 'sold_at']);

        $fastest = null;
        foreach ($soldProperties as $prop) {
            $days = (int) abs($prop->sold_at->diffInDays($prop->created_at));
            if ($days <= 0) {
                continue;
            }
            if ($fastest === null || $days < $fastest['days']) {
                $fastest = ['user_id' => $prop->owner_user_id, 'days' => $days];
            }
        }

        if ($fastest) {
            $user = \App\Models\User::find($fastest['user_id']);
            $leaders[] = [
                'category' => 'Ātrākais pārdošanas cikls',
                'leader_name' => $user?->name ?? '—',
                'leader_value' => $fastest['days'],
                'leader_avatar' => $user?->avatar_path,
            ];
        } else {
            $leaders[] = array_merge(['category' => 'Ātrākais pārdošanas cikls'], $empty());
        }

        return $leaders;
    }
}
