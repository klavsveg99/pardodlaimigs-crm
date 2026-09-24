<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FollowUpLeadResource\Pages;

use App\Filament\Admin\Resources\FollowUpLeadResource;
use App\Filament\Admin\Resources\Pages\Concerns\RefreshesTabBadges;
use App\Models\FollowUpLead;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFollowUpLeads extends ListRecords
{
    use RefreshesTabBadges;

    protected static string $resource = FollowUpLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Jauns Follow Up līdis')->color('gray')];
    }

    public function getTabs(): array
    {
        // Aģentiem skaitītāji rāda tikai viņa paša līdus.
        $count = fn (Builder $query): int => $query->when(
            ! auth()->user()?->can('manage'),
            fn ($q) => $q->where('owner_user_id', auth()->id()),
        )->count();

        return [
            'active' => Tab::make('Aktīvie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge($count(FollowUpLead::query()->where('status', 'active'))),
            'closed' => Tab::make('Noslēgtie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', '!=', 'active'))
                ->badge($count(FollowUpLead::query()->where('status', '!=', 'active'))),
        ];
    }
}
