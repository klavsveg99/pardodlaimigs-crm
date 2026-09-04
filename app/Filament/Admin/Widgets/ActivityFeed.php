<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Activity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ActivityFeed extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public const TYPE_LABELS = [
        'created' => 'Izveidots darījums',
        'stage_changed' => 'Mainīta darījuma stadija',
        'viewing_booked' => 'Pieteikta apskate',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Uzņēmuma aktivitātes')
            ->description('Jaunākie notikumi sistēmā')
            ->query(fn () => $this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Laiks')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Darbība')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::TYPE_LABELS[$state] ?? $state),

                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Dalībnieks')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('detail')
                    ->label('Detalizācija')
                    ->wrap()
                    ->limit(60)
                    ->getStateUsing(fn ($record) => $this->describe($record)),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function getQuery(): Builder
    {
        return Activity::query()
            ->with(['actor', 'deal', 'client'])
            // Drop activities whose related records were deleted — they would
            // render as broken/stale rows (e.g. test viewings removed from CRM).
            ->where(fn ($q) => $q->whereNull('client_id')->orWhereHas('client'))
            ->where(fn ($q) => $q->whereNull('deal_id')->orWhereHas('deal'))
            ->where(fn ($q) => $q->whereNull('actor_user_id')->orWhereHas('actor'))
            ->orderByDesc('created_at')
            ->limit(50);
    }

    protected function describe(Activity $activity): string
    {
        $payload = $activity->payload ?? [];
        $parts = [];

        if (isset($payload['from']) && isset($payload['to'])) {
            $from = is_string($payload['from']) ? $payload['from'] : '—';
            $to = is_string($payload['to']) ? $payload['to'] : '—';
            $parts[] = $from.' → '.$to;
        }

        if ($activity->deal) {
            $parts[] = 'Darījums: '.$activity->deal->title;
        }
        if ($activity->client) {
            $parts[] = 'Klients: '.$activity->client->name;
        }
        if ($activity->type === 'viewing_booked' && isset($payload['scheduled_at'])) {
            $parts[] = 'Apskate: '.\Carbon\Carbon::parse($payload['scheduled_at'])->format('d.m.Y H:i');
        }

        return implode(' · ', $parts) ?: '—';
    }
}
