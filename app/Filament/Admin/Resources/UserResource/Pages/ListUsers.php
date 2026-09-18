<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\RefreshesTabBadges;
use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    use RefreshesTabBadges;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Jauns aģents')
                ->color('gray'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Aktīvie')
                ->badge(User::query()->count()),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed())
                ->badge(User::onlyTrashed()->count()),
        ];
    }
}
