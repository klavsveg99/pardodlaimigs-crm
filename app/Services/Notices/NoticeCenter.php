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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Vienots aizveramo paziņojumu avots. Tos rāda gan "Šodien jāizdara"
 * widgetā, gan augšējā labā stūra paziņojumos; aizvēršana tiek glabāta
 * notification_dismissals tabulā, tāpēc abas vietas vienmēr sakrīt.
 *
 * Paziņojumu veidi:
 *  - lead: klienti ar statusu "Līds" (atbildīgajam aģentam), atgriežas katru dienu;
 *  - stale_property: aktīvi īpašumi, kuru cena/statuss nav mainīti 45 dienas;
 *  - birthday: "Laimīgo" klientu dzimšanas dienas tuvākajās dienās.
 */
class NoticeCenter
{
    public const LEAD_KEY_PREFIX = 'lead:';

    public const STALE_KEY_PREFIX = 'stale_property:';

    public const BIRTHDAY_KEY_PREFIX = 'birthday:';

    public const BIRTHDAY_DAY_KEY_PREFIX = 'birthday_day:';

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
            $this->birthdayNotices($user, $dismissals),
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
            // Nerādām pavisam jaunu, neaiztiktu līdu tajā pašā dienā, kad tas
            // izveidots — dodam dienu, pirms tas sāk parādīties paziņojumos.
            ->where(function ($query): void {
                $query->where('created_at', '<', now()->startOfDay())
                    ->orWhereColumn('updated_at', '>', 'created_at');
            })
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
                    ['label' => 'Pārdošanas sākums', 'value' => $property->sale_started_at?->format('d.m.Y') ?? '—'],
                    ['label' => 'Līguma periods', 'value' => $property->partnership_label],
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * Dzimšanas dienas atgādinājums tikai "Laimīgajiem" klientiem. Ir DIVI
     * atgādinājumi:
     *  - pirmsdzimšanas dienas: sāk parādīties config('crm.birthdays.days_before')
     *    dienas pirms dzimšanas dienas un turpina parādīties KATRU dienu, līdz
     *    aģents to aizver (X atzīmē uz visu gadu) vai atzīmē "apsveikts";
     *  - dzimšanas dienas dienā: atsevišķs atgādinājums pašā dzimšanas dienā
     *    (parādās arī tad, ja iepriekšējais jau aizvērts).
     * Atzīmēšana kā apsveikts dzēš abus uz kārtējo gadu.
     *
     * @param  Collection<string, NotificationDismissal>  $dismissals
     * @return array<int, array<string, mixed>>
     */
    private function birthdayNotices(User $user, Collection $dismissals): array
    {
        $today = now()->startOfDay();
        $daysBefore = (int) config('crm.birthdays.days_before', 3);

        return Client::query()
            ->where('status', 'laimigs')
            ->whereNotNull('birth_date')
            ->when(! $user->can('manage'), fn ($query) => $query->where('owner_user_id', $user->id))
            ->orderBy('birth_date')
            ->get()
            ->map(function (Client $client) use ($today, $daysBefore): ?array {
                $birthday = $client->birthdayThisYear();

                if (! $birthday || $client->birthday_greeted_at?->year === $today->year) {
                    return null;
                }

                $daysUntil = (int) $today->diffInDays($birthday, false);

                if ($daysUntil === 0) {
                    $key = self::BIRTHDAY_DAY_KEY_PREFIX.$client->id;
                } elseif ($daysUntil >= 1 && $daysUntil <= $daysBefore) {
                    $key = self::BIRTHDAY_KEY_PREFIX.$client->id;
                } else {
                    return null;
                }

                return [
                    'key' => $key,
                    'type' => 'Dzimšanas diena',
                    'type_color' => 'gray',
                    'icon' => 'heroicon-o-gift',
                    'urgent' => false,
                    'title' => $client->name,
                    'url' => ClientResource::getUrl('view', ['record' => $client]),
                    'greet_client_id' => $client->id,
                    'fields' => array_values(array_filter([
                        ['label' => 'Dzimšanas diena', 'value' => $client->birth_date?->format('d.m.')],
                        $client->phone ? ['label' => 'Tālrunis', 'value' => PhoneFormat::display($client->phone)] : null,
                        $client->email ? ['label' => 'E-pasts', 'value' => $client->email] : null,
                    ])),
                ];
            })
            ->filter()
            ->reject(fn (array $notice): bool => $this->dismissedThisYear($dismissals->get($notice['key']), $today))
            ->values()
            ->all();
    }

    /**
     * Dzimšanas dienas atgādinājums ir aizvērts uz visu kārtējo gadu —
     * aizvēršana to aptur, nevis tikai paslēpj uz dienu.
     */
    private function dismissedThisYear(?NotificationDismissal $dismissal, Carbon $today): bool
    {
        return $dismissal?->dismissed_at?->year === $today->year;
    }

    /**
     * Paziņojums ir aizvērts tikai šodien — nākamajā dienā tas parādās
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
