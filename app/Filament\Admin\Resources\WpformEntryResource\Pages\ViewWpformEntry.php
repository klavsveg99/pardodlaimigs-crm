<?php

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\WpformEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWpformEntry extends ViewRecord
{
    protected static string $resource = WpformEntryResource::class;

    protected string $view = 'filament.admin.resources.wpform-entry-resource.pages.view-wpform-entry';

    public function getTitle(): string
    {
        return 'Formas ieraksts';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_viewed')
                ->label(fn () => $this->record->viewed ? 'Atzīmēt kā nelasītu' : 'Atzīmēt kā lasītu')
                ->icon(fn () => $this->record->viewed ? 'heroicon-o-envelope' : 'heroicon-o-envelope-open')
                ->color(fn () => $this->record->viewed ? 'gray' : 'success')
                ->requiresConfirmation(false)
                ->action(function () {
                    $this->record->update(['viewed' => !$this->record->viewed]);
                    \Filament\Notifications\Notification::make()
                        ->title($this->record->viewed ? 'Atzīmēts kā lasīts' : 'Atzīmēts kā nelasīts')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make()->label('Dzēst'),
        ];
    }
}