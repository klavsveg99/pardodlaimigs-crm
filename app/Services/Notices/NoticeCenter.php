<?php

declare(strict_types=1);

namespace App\Services\Notices;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\NotificationDismissal;
use App\Models\User;
use App\Support\PhoneFormat;
use Illuminate\Support\Collection;

/**
 * Vienots aizveramo paziņojumu avots. Tos rāda gan "Šodien jāizdara"
 * widgetā, gan augšējā labā stūra paziņojumos; aizvēršana tiek glabāta
 * notification_dismissals tabulā, tāpēc abas vietas vienmēr sakrīt.
 *
 * Paziņojumu veidi:
 *  - lead: klienti ar statusu "Līds" (atbildīgajam aģentam);
 *  - stale_property: aktīvi īpašumi, kuru cena/statuss nav mainīti 45 dienas.
 */
class NoticeCenter
{
    /** Pēc cik dienām aizvērts "līds" atkal parādās (~divas reizes nedēļā). */
    public const LEAD_RESURFACE_DAYS = 4;

    public const LEAD_KEY_PREFIX = 'lead:';

    public const STALE_KEY_PREFIX = 'stale_property:';

    /** @return array<int, array<string, mixed>> */
    public function forUser(User $user): array
    {
        $dismissals = NotificationDismissal::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('key');

        return array_values(array_merge(
            $this->leadNotices($user, $dismissals),
            $this->stalePropertyNotices($user, $dismissals),
        ));
    }

    public function dismiss(User $user, string $key): void
    {
        NotificationDismissal::query()->updateOrCreate(
            ['user_id' => $user->id, 'key' => $key],
            ['dismissed_at' => now()],
        );
    }

    /**
     * @param  Collection<string, NotificationDismissal>  $dismissals
     * @return array<int, array<string, mixed>>
     */
    private function leadNotices(User $user, Collection $dismissals): array
    {
        return Client::query()
            ->where('status', 'lead')
            ->when(! $user->can('manage'), fn ($query) => $query->where('owner_user_id', $user->id))
            ->with('owner')
            ->orderBy('updated_at')
            ->get()
            ->reject(fn (Client $client): bool => $this->leadDismissed($dismissals->get(self::LEAD_KEY_PREFIX.$client->id)))
            ->map(fn (Client $client): array => [
                'key' => self::LEAD_KEY_PREFIX.$client->id,
                'type' => 'Līds',
                'type_color' => 'warning',
                'icon' => 'heroicon-o-phone-arrow-up-right',
                'urgent' => false,
                'title' => $client->name,
                'url' => ClientResource::getUrl('view', ['record' => $client]),
                'fields' => array_values(array_filter([
                    $client->phone ? ['label' => 'Tālrunis', 'value' => PhoneFormat::display($client->phone)] : null,
                    $client->email ? ['label' => 'E-pasts', 'value' => $client->email] : null,
                    $client->source ? ['label' => 'Avots', 'value' => $client->source] : null,
                    ($user->can('manage') && $client->owner) ? ['label' => 'Aģents', 'value' => $client->owner->name] : null,
                ])),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<string, NotificationDismissal>  $dismissals
     * @return array<int, array<string, mixed>>
     */
    private function stalePropertyNotices(User $user, Collection $dismissals): array
    {
        if ($user->isPhoto()) {
            return [];
        }

        return CrmProperty::query()
            ->priceStatusStale()
            ->when(! $user->can('manage'), fn ($query) => $query->where('owner_user_id', $user->id))
            ->orderBy('price_status_changed_at')
            ->get()
            ->reject(fn (CrmProperty $property): bool => $this->staleDismissed($dismissals->get(self::STALE_KEY_PREFIX.$property->id), $property))
            ->map(fn (CrmProperty $property): array => [
                'key' => self::STALE_KEY_PREFIX.$property->id,
                'type' => 'Īpašums',
                'type_color' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
                'urgent' => true,
                'title' => $property->title,
                'url' => CrmPropertyResource::getUrl('view', ['record' => $property]),
                'fields' => [
                    ['label' => 'Bez izmaiņām', 'value' => $property->daysWithoutPriceStatusChange().' dienas'],
                    ['label' => 'Pārdošanas sākums', 'value' => $property->sale_started_at?->format('d.m.Y') ?? '—'],
                    ['label' => 'Līguma periods', 'value' => $property->partnership_label],
                ],
            ])
            ->values()
            ->all();
    }

    private function leadDismissed(?NotificationDismissal $dismissal): bool
    {
        if (! $dismissal?->dismissed_at) {
            return false;
        }

        return $dismissal->dismissed_at->gt(now()->subDays(self::LEAD_RESURFACE_DAYS));
    }

    private function staleDismissed(?NotificationDismissal $dismissal, CrmProperty $property): bool
    {
        if (! $dismissal?->dismissed_at) {
            return false;
        }

        // Atgriežas tikai pēc tam, kad cena/statuss mainīts pēc aizvēršanas.
        return $dismissal->dismissed_at->gt($property->price_status_changed_at ?? $property->updated_at);
    }
}
