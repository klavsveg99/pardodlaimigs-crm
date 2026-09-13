<?php

namespace App\Filament\Admin\Resources\TaskResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use App\Filament\Admin\Resources\TaskResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = TaskResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt uzdevumu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('complete')
                ->label('Pabeigt')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => ! $this->getRecord()->completed_at)
                ->action(function () {
                    $this->getRecord()->update(['completed_at' => now()]);
                    Notification::make()->title('Uzdevums izpildīts')->success()->send();
                    $this->refresh();
                }),
            Actions\Action::make('reopen')
                ->label('Atzīmēt kā neizpildītu')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn () => (bool) $this->getRecord()->completed_at)
                ->action(function () {
                    $this->getRecord()->update(['completed_at' => null]);
                    Notification::make()->title('Uzdevums atzīmēts kā neizpildīts')->send();
                    $this->refresh();
                }),
        ];
    }
}
