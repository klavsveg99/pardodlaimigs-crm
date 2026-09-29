<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

/**
 * Standard "undo" pattern for Filament Livewire components.
 *
 * After completing an action, call {@see notifyUndoable()} with an action key
 * and the record id. The success toast then carries an "Atsaukt" button that
 * dispatches the {@see self::UNDO_EVENT} Livewire event; whichever mounted
 * component implements {@see applyUndo()} for that key reverts the change.
 *
 * Only the record id travels in the event payload — the revert always
 * re-queries the record using the same scoping as the original action, so a
 * forged event cannot modify arbitrary data.
 */
trait HasUndoableNotifications
{
    public const UNDO_EVENT = 'pdc-undo';

    /**
     * @param  array<string, mixed>  $data
     */
    protected function notifyUndoable(string $title, string $action, array $data = [], string $undoLabel = 'Atsaukt'): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->seconds(10)
            ->actions([
                Action::make('undo')
                    ->label($undoLabel)
                    ->color('gray')
                    ->close()
                    ->dispatch(self::UNDO_EVENT, ['action' => $action, 'data' => $data]),
            ])
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[On('pdc-undo')]
    public function undoAction(string $action, array $data = []): void
    {
        if (! $this->applyUndo($action, $data)) {
            return;
        }

        Notification::make()
            ->title('Atsaukts')
            ->success()
            ->send();
    }

    /**
     * Revert a previously completed action. Return true when the action was
     * recognised and reverted.
     *
     * @param  array<string, mixed>  $data
     */
    abstract protected function applyUndo(string $action, array $data): bool;
}
