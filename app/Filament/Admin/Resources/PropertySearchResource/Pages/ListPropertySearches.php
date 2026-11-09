<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PropertySearchResource\Pages;

use App\Filament\Admin\Resources\PropertySearchResource;
use App\Jobs\RunPropertySearch;
use App\Models\PropertySearch;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPropertySearches extends ListRecords
{
    protected static string $resource = PropertySearchResource::class;

    public function getTitle(): string
    {
        return 'Meklētāji';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('run_all')
                ->label('Palaist visus')
                ->icon('heroicon-o-play')
                ->color('gray')
                ->action(function (): void {
                    $query = PropertySearch::query()
                        ->where('is_active', true)
                        ->when(
                            ! auth()->user()?->can('manage'),
                            fn ($query) => $query->where('user_id', auth()->id()),
                        );

                    $count = 0;
                    foreach ($query->pluck('id') as $id) {
                        RunPropertySearch::dispatch((int) $id);
                        $count++;
                    }

                    Notification::make()
                        ->title('Meklēšanas palaistas')
                        ->body('Ieplānoti meklētāji: '.$count.'.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()->label('Jauns meklētājs')->color('gray'),
        ];
    }
}
