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
    protected static ?int $sort = 3;

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
            ->extraAttributes(['class' => 'pdc-category-leaders'])
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
                Tables\Columns\TextColumn::make('leader_avatar')
                    ->label('')
                    ->html()
                    // Fiksēts platums, citādi tabula (w-full) atstāj visu
                    // atlikušo platumu šai kolonnai un pārējās aizbīdās malā.
                    ->width('36px')
                    ->getStateUsing(fn ($record) => $this->avatarStackHtml($record['leaders'] ?? [])),
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
        $empty = fn () => ['leader_name' => '—', 'leader_value' => '—', 'leaders' => []];

        // Uzvarētāju saraksts no lietotāju ID; saglabājam to secību. Ja
        // vairāki dalībnieki sasniedz vienādu vērtību, visi ir uzvarētāji.
        $build = function (string $category, $value, array $userIds): array {
            $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

            $leaders = [];
            foreach (array_values(array_unique($userIds)) as $id) {
                $user = $users->get($id);
                if (! $user) {
                    continue;
                }
                $leaders[] = ['name' => (string) $user->name, 'avatar' => $user->avatar_url];
            }

            return [
                'category' => $category,
                'leader_value' => $value,
                'leader_name' => $leaders === [] ? '—' : implode(', ', array_column($leaders, 'name')),
                'leaders' => $leaders,
            ];
        };

        // Jauni klienti
        $newClients = Client::whereBetween('created_at', [$start, $end])
            ->whereNotNull('owner_user_id')
            ->get(['owner_user_id'])
            ->groupBy('owner_user_id')
            ->map->count()
            ->sortDesc();

        if ($newClients->count()) {
            $winning = $newClients->first();
            $leaders[] = $build(
                'Jauni klienti',
                $winning,
                $newClients->filter(fn ($count) => $count === $winning)->keys()->all(),
            );
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
            $winning = $viewingLeader->first();
            $leaders[] = $build(
                'Organizētas apskates',
                $winning,
                $viewingLeader->filter(fn ($count) => $count === $winning)->keys()->all(),
            );
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
            $winning = $soldLeader->first();
            $leaders[] = $build(
                'Pārdoti īpašumi',
                $winning,
                $soldLeader->filter(fn ($count) => $count === $winning)->keys()->all(),
            );
        } else {
            $leaders[] = array_merge(['category' => 'Pārdoti īpašumi'], $empty());
        }

        // Ātrākais pārdošanas cikls — dienas no pārdošanas sākuma datuma
        // (sale_started_at) līdz dienai, kad statuss kļuva "Pārdots"
        // (sold_at). Tā kā uzvarētājs tiek meklēts tikai starp statusa
        // "sold" īpašumiem, atgriežot statusu atpakaļ ieraksts pazūd.
        $soldProperties = CrmProperty::query()
            ->where('status', 'sold')
            ->whereNotNull('owner_user_id')
            ->whereNotNull('sale_started_at')
            ->whereNotNull('sold_at')
            ->whereBetween('sold_at', [$start, $end])
            ->get(['owner_user_id', 'sale_started_at', 'sold_at']);

        $fastestDays = null;
        $fastestUserIds = [];
        foreach ($soldProperties as $prop) {
            $days = (int) $prop->sale_started_at->copy()->startOfDay()
                ->diffInDays($prop->sold_at->copy()->startOfDay());
            if ($days < 0) {
                continue;
            }
            if ($fastestDays === null || $days < $fastestDays) {
                $fastestDays = $days;
                $fastestUserIds = [(int) $prop->owner_user_id];
            } elseif ($days === $fastestDays) {
                $fastestUserIds[] = (int) $prop->owner_user_id;
            }
        }

        if ($fastestDays !== null) {
            $leaders[] = $build('Ātrākais pārdošanas cikls', $fastestDays, $fastestUserIds);
        } else {
            $leaders[] = array_merge(['category' => 'Ātrākais pārdošanas cikls'], $empty());
        }

        return $leaders;
    }

    /**
     * Sakrautas uzvarētāju fotogrāfijas: nākamā 50% pārklāj iepriekšējo un
     * uz hover nāk priekšā (skat. .pdc-avatar-stack CSS).
     *
     * @param  array<int, array{name: string, avatar: ?string}>  $leaders
     */
    protected function avatarStackHtml(array $leaders): string
    {
        $fallback = asset('images/no-photo.svg');

        if ($leaders === []) {
            return '<span class="pdc-avatar-stack"><img src="'.e($fallback).'" alt=""></span>';
        }

        $html = '<span class="pdc-avatar-stack">';
        $zIndex = count($leaders);
        foreach ($leaders as $leader) {
            $html .= '<img src="'.e($leader['avatar'] ?: $fallback).'"'
                .' alt="'.e($leader['name']).'"'
                .' title="'.e($leader['name']).'"'
                .' style="z-index: '.$zIndex.'">';
            $zIndex--;
        }

        return $html.'</span>';
    }
}
