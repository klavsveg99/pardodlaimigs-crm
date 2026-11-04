<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Services\KnowledgeRecap;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AgentKnowledgeMaterialsTable extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public ?int $agentId = null;

    protected function makeTable(): Table
    {
        return $this->makeBaseTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Materiāli')
            ->extraAttributes(['class' => 'pdc-agent-report-table'])
            ->records(fn (): array => $this->records())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Materiāls')
                    ->weight('bold')
                    ->url(fn (array $record): ?string => $record['url'] ?? null),

                Tables\Columns\TextColumn::make('availability')
                    ->label('Pieejamība')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Termiņš')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn ($record): string => ($record['overdue'] ?? false) ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Pabeigts' ? 'success' : 'warning'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nav materiālu')
            ->emptyStateDescription('Nav atrasts neviens obligāts vai piešķirts materiāls.')
            ->emptyStateIcon('heroicon-o-book-open');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function records(): array
    {
        $user = User::find($this->agentId);

        return $user ? app(KnowledgeRecap::class)->materials($user) : [];
    }
}
