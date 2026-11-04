<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Services\KnowledgeRecap;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AgentKnowledgeCoursesTable extends BaseWidget
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
            ->heading('Piešķirtie kursi')
            ->extraAttributes(['class' => 'pdc-agent-report-table'])
            ->records(fn (): array => $this->records())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kurss')
                    ->weight('bold'),

                Tables\Columns\ViewColumn::make('progress')
                    ->label('Progress')
                    ->view('filament.tables.columns.knowledge-course-progress'),

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

                Tables\Columns\TextColumn::make('rating')
                    ->label('Vērtējums')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? str_repeat('★', $state).str_repeat('☆', 5 - $state)
                        : '-')
                    ->color('warning'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nav piešķirtu kursu')
            ->emptyStateDescription('Kad lietotājam tiks piešķirts kurss, tas parādīsies šeit.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function records(): array
    {
        $user = User::find($this->agentId);

        return $user ? app(KnowledgeRecap::class)->courses($user) : [];
    }
}
