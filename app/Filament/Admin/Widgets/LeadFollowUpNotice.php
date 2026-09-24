<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ClientResource;
use App\Models\Client;
use App\Support\PhoneFormat;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Atgādinājums atbildīgajam aģentam par klientiem ar statusu "Līdis" —
 * jāsazinās un jāatjaunina informācija. Aizverams; pēc aizvēršanas paslēpts
 * uz ~4 dienām, tāpēc parādās ne vairāk kā ~divas reizes nedēļā.
 *
 * Pēc noņemšanas vienkārši izdzēs šo failu un tā blade veidni.
 */
class LeadFollowUpNotice extends Widget
{
    protected string $view = 'filament.admin.widgets.lead-follow-up-notice';

    protected int|string|array $columnSpan = 'full';

    /** Nav slinka ielāde — paziņojumam jābūt redzamam uzreiz. */
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    /** Cik ilgi pēc aizvēršanas paziņojums vairs nerādās. */
    private const DISMISS_HOURS = 96;

    public static function canView(): bool
    {
        $user = auth()->user();

        if ($user?->isPhoto()) {
            return false;
        }

        return ! static::isSuppressed() && static::leadsQuery()->exists();
    }

    /** @return Builder<Client> */
    protected static function leadsQuery(): Builder
    {
        $user = auth()->user();

        return Client::query()
            ->where('status', 'lead')
            ->when(
                ! $user?->can('manage'),
                fn (Builder $query) => $query->where('owner_user_id', $user?->id),
            );
    }

    protected static function cacheKey(): string
    {
        return 'lead-notice-dismissed:'.(auth()->id() ?? 0);
    }

    protected static function isSuppressed(): bool
    {
        return Cache::has(static::cacheKey());
    }

    /** @return array<int, array<string, mixed>> */
    public function getLeads(): array
    {
        return static::leadsQuery()
            ->with('owner')
            ->orderBy('updated_at')
            ->limit(50)
            ->get()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'url' => ClientResource::getUrl('view', ['record' => $client]),
                'phone' => PhoneFormat::display($client->phone ?? null),
                'email' => $client->email,
                'source' => $client->source,
                'agent' => $client->owner?->name,
                'created' => $client->created_at?->format('d.m.Y'),
            ])
            ->all();
    }

    public function dismiss(): void
    {
        Cache::put(static::cacheKey(), true, now()->addHours(self::DISMISS_HOURS));
        $this->dispatch('$refresh');
    }
}
