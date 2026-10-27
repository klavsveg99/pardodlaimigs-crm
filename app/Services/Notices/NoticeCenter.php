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
 *  - follow_up: līdi, kuriem pienācis norādītais saziņas datums (atbildīgajam
 *    aģentam). Paliek redzams katru dienu, līdz to aizver;
 *  - lead: klienti ar statusu "Līds" bez norādīta saziņas datuma
 *    (atbildīgajam aģentam), atgriežas katru dienu;
 *  - stale_property: aktīvi īpašumi, kuru cena/statuss nav mainīti 45 dienas.
 *
 * Klientu dzimšanas dienu apsveikumus tagad sūta automātiski (pdc:send-greetings),
 * tāpēc tie vairs nav manuāli aizverami paziņojumi.
 */
class NoticeCenter
{
    public const LEAD_KEY_PREFIX = 'lead:';

    public const FOLLOW_UP_KEY_PREFIX = 'follow_up:';

    public const STALE_KEY_PREFIX = 'stale_property:';

    /** @return array<int, array<string, mixed>> */
    public function forUser(User $user): array
    {
        // Students redz tikai zināšanu bāzi - CRM paziņojumi viņu neskar.
        if ($user->isStudent()) {
            return [];
        }

        $dismissals = NotificationDismissal::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('key');

        return array_values(array_merge(
            $this->followUpNotices($user, $dismissals),
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
     * Līdi, kuriem ir pienācis norādītais saziņas datums. Atgādinājums
     * paliek redzams, līdz to aizver; ja datums tiek nomainīts, tas parādās
     * no jauna (atslēga satur datumu).
     *
     * @param  Collection<string, NotificationDismissal>  $dismissals
     * @return array<int, array<string, mixed>>
     */
    private function followUpNotices(User $user, Collection $dismissals): array
    {
        if ($user->isPhoto()) {
            return [];
        }

        return Client::query()
            ->where('status', 'lead')
            ->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', '<=', now()->toDateString())
            ->when(! $user->can('manage'), fn ($query) => $query->where('owner_user_id', $user->id))
            ->with('owner')
            ->orderBy('follow_up_at')
            ->get()
            ->reject(fn (Client $client): bool => $dismissals->has($this->followUpKey($client)))
            ->map(fn (Client $client): array => [
                'key' => $this->followUpKey($client),
                'type' => 'Follow-up',
                'type_color' => 'gray',
                'icon' => 'heroicon-o-calendar-days',
                'urgent' => false,
                'title' => $client->name,
                'url' => ClientResource::getUrl('view', ['record' => $client]),
                'fields' => array_values(array_filter([
                    ['label' => 'Saziņa', 'value' => $client->follow_up_at?->format('d.m.Y')],
                    $client->follow_up_note ? ['label' => 'Piezīme', 'value' => $client->follow_up_note] : null,
                    $client->phone ? ['label' => 'Tālrunis', 'value' => PhoneFormat::display($client->phone)] : null,
                    ($user->can('manage') && $client->owner) ? ['label' => 'Aģents', 'value' => $client->owner->name] : null,
                ])),
            ])
            ->values()
            ->all();
    }

    /**
     * Atslēga satur arī datumu, tāpēc cita datuma atgādinājums ir atsevišķs
     * paziņojums un pēc aizvēršanas atgriežas tikai tad, ja datums mainīts.
     */
    private function followUpKey(Client $client): string
    {
        return self::FOLLOW_UP_KEY_PREFIX.$client->id.':'.($client->follow_up_at?->toDateString() ?? '');
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
            // Nerādām pavisam jaunu, neaiztiktu līdu tajā pašā dienā, kad tas
            // izveidots - dodam dienu, pirms tas sāk parādīties paziņojumos.
            ->where(function ($query): void {
                $query->where('created_at', '<', now()->startOfDay())
                    ->orWhereColumn('updated_at', '>', 'created_at');
            })
            // Ja līdam ir norādīts saziņas datums, vispārīgo līda paziņojumu
            // nerāda - konkrētais Follow-up atgādinājums parādās tikai no tā
            // datuma (skat. followUpNotices), nevis katru dienu pirms tam.
            ->whereNull('follow_up_at')
            ->with('owner')
            ->orderBy('updated_at')
            ->get()
            ->reject(fn (Client $client): bool => $this->dismissedToday($dismissals->get(self::LEAD_KEY_PREFIX.$client->id)))
            ->map(fn (Client $client): array => [
                'key' => self::LEAD_KEY_PREFIX.$client->id,
                'type' => 'Līds',
                'type_color' => 'gray',
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
                'type_color' => 'gray',
                'icon' => 'heroicon-o-exclamation-triangle',
                'urgent' => true,
                'title' => $property->title,
                'url' => CrmPropertyResource::getUrl('view', ['record' => $property]),
                'fields' => [
                    ['label' => 'Bez izmaiņām', 'value' => $property->daysWithoutPriceStatusChange().' dienas'],
                    ['label' => 'Pārdošanas sākums', 'value' => $property->sale_started_at?->format('d.m.Y') ?? '-'],
                    ['label' => 'Līguma periods', 'value' => $property->partnership_label],
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * Paziņojums ir aizvērts tikai šodien - nākamajā dienā tas parādās
     * atkal (kamēr pastāv tā pamatnosacījums).
     */
    private function dismissedToday(?NotificationDismissal $dismissal): bool
    {
        return (bool) $dismissal?->dismissed_at?->isToday();
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
