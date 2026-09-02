<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\PropertyCache;
use App\Models\Task;
use App\Models\User;
use App\Models\Viewing;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class QuickAdd extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ātrais līdzeklis')
            ->description('Ātri pievieno jaunus ierakstus sistēmā')
            ->query(
                Client::query()->orderByDesc('created_at')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Klienti (jaunākie)')->weight('bold')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('owner.name')->label('Aģents')->placeholder('—'),
            ])
            ->headerActions([
                Action::make('quick_task')
                    ->label('Ātri pievienot uzdevumu')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->label('Uzdevums')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('assigned_user_id')
                            ->label('Atbildīgais')
                            ->searchable()
                            ->options(fn () => User::query()->orderBy('name')->limit(50)->pluck('name', 'id')->all()),
                        Forms\Components\Select::make('client_id')
                            ->label('Klients')
                            ->searchable()
                            ->options(fn () => Client::query()->orderBy('name')->limit(50)->pluck('name', 'id')->all()),
                        Forms\Components\DateTimePicker::make('due_at')
                            ->label('Izpildes termiņš')
                            ->default(now()),
                    ])
                    ->action(function (array $data): void {
                        Task::create([
                            'title' => $data['title'],
                            'assigned_user_id' => $data['assigned_user_id'] ?? null,
                            'client_id' => $data['client_id'] ?? null,
                            'created_by_user_id' => auth()->id(),
                            'due_at' => $data['due_at'] ?? null,
                        ]);
                        Notification::make()->title('Uzdevums pievienots')->success()->send();
                    }),

                Action::make('quick_viewing')
                    ->label('Ātri pievienot apskati')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('property_id')
                            ->label('Īpašums')
                            ->searchable()
                            ->options(fn () => PropertyCache::query()->limit(50)->pluck('title', 'id')->all())
                            ->getOptionLabelUsing(fn ($value) => PropertyCache::find($value)?->selection_label),
                        Forms\Components\Select::make('client_id')
                            ->label('Klients')
                            ->searchable()
                            ->options(fn () => Client::query()->orderBy('name')->limit(50)->pluck('name', 'id')->all()),
                        Forms\Components\Select::make('agent_user_id')
                            ->label('Aģents')
                            ->searchable()
                            ->default(fn () => auth()->id())
                            ->options(fn () => User::query()->orderBy('name')->limit(50)->pluck('name', 'id')->all()),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Apskates laiks')
                            ->default(now()->addDay())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        Viewing::create([
                            'property_id' => $data['property_id'] ?? null,
                            'client_id' => $data['client_id'] ?? null,
                            'agent_user_id' => $data['agent_user_id'] ?? auth()->id(),
                            'scheduled_at' => $data['scheduled_at'],
                            'status' => 'scheduled',
                        ]);
                        Notification::make()->title('Apskate pievienota')->success()->send();
                    }),
            ])
            ->paginated(false);
    }
}
