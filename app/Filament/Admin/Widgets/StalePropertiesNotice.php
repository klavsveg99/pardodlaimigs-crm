<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\CrmProperty;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Brīdinājums īpašumu saraksta augšā: aktīvi īpašumi, kuru cena un statuss nav
 * mainīti 45 dienas. Aizvēršana ir noturīga — brīdinājums atgriežas tikai pēc
 * nākamās cenas/statusa izmaiņas.
 */
class StalePropertiesNotice extends Widget
{
    protected string $view = 'filament.admin.widgets.stale-properties-notice';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return static::query()->exists();
    }

    /** @return Builder<CrmProperty> */
    protected static function query(): Builder
    {
        $user = auth()->user();

        return CrmProperty::query()
            ->priceStatusStale()
            ->when(! $user?->can('manage') && ! $user?->isPhoto(), fn ($q) => $q->where('owner_user_id', $user?->id))
            ->where(function ($q): void {
                $q->whereNull('stale_notice_dismissed_at')
                    ->orWhereColumn('stale_notice_dismissed_at', '<', 'price_status_changed_at');
            });
    }

    /** @return array<int, array<string, mixed>> */
    public function getProperties(): array
    {
        return static::query()
            ->orderBy('price_status_changed_at')
            ->get()
            ->map(fn (CrmProperty $property): array => [
                'id' => $property->id,
                'title' => $property->title,
                'url' => route('filament.admin.resources.properties.view', $property),
                'days' => $property->daysWithoutPriceStatusChange(),
                'sale_started_at' => $property->sale_started_at?->format('d.m.Y') ?? '—',
                'partnership' => $property->partnership_label,
            ])
            ->all();
    }

    public function dismiss(): void
    {
        $ids = collect($this->getProperties())->pluck('id')->all();

        if ($ids !== []) {
            CrmProperty::query()->whereIn('id', $ids)->update(['stale_notice_dismissed_at' => now()]);
        }

        Notification::make()
            ->title('Brīdinājums aizvērts')
            ->body('Atgriezīsies tikai pēc cenas vai statusa maiņas.')
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }
}
