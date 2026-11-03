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
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Url;

class ListUsers extends ListRecords
{
    use RefreshesTabBadges;

    protected static string $resource = UserResource::class;

    /** Lietotāja tipa filtrs virs tabulas. */
    #[Url(as: 'role')]
    public string $roleFilter = '';

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        $query = parent::getTableQuery();

        if (! $query instanceof Builder || $this->roleFilter === '') {
            return $query;
        }

        if ($this->roleFilter === 'aģents') {
            return $query->whereIn('role', ['aģents', 'agent']);
        }

        return $query->where('role', $this->roleFilter);
    }

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
