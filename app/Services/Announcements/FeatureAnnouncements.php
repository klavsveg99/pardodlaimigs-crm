<?php

declare(strict_types=1);

namespace App\Services\Announcements;

use App\Filament\Admin\Pages\MyProfile;
use App\Filament\Admin\Resources\PropertySearchResource;
use App\Filament\Admin\Resources\UserResource;
use App\Models\FeatureAnnouncementView;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * One-time "new feature" popups. Add a new entry to definitions() and the
 * popup appears once for every matching user; no bespoke Blade partial or
 * route is needed. Only the first unseen announcement is shown at a time.
 */
class FeatureAnnouncements
{
    /** @return array<string, mixed>|null */
    public function nextFor(User $user): ?array
    {
        if (! Schema::hasTable('feature_announcement_views')) {
            return null;
        }

        $seen = FeatureAnnouncementView::query()
            ->where('user_id', $user->id)
            ->pluck('feature_key')
            ->all();

        foreach ($this->definitions() as $key => $definition) {
            if (in_array($key, $seen, true)) {
                continue;
            }

            $audience = $definition['audience'] ?? null;
            if ($audience instanceof \Closure && ! $audience($user)) {
                continue;
            }

            $linkUrl = $definition['link_url'] ?? null;
            if ($linkUrl instanceof \Closure) {
                $linkUrl = $linkUrl($user);
            }

            return [
                'key' => $key,
                'kicker' => $definition['kicker'],
                'title' => $definition['title'],
                'body' => $definition['body'],
                'bullets' => $definition['bullets'] ?? [],
                'link_label' => $definition['link_label'] ?? null,
                'link_url' => $linkUrl,
            ];
        }

        return null;
    }

    public function markSeen(User $user, string $key): void
    {
        if (! array_key_exists($key, $this->definitions())) {
            return;
        }

        if (! Schema::hasTable('feature_announcement_views')) {
            return;
        }

        FeatureAnnouncementView::query()->updateOrCreate(
            ['user_id' => $user->id, 'feature_key' => $key],
            ['seen_at' => now()],
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            // Newer announcements first: the signature notice should reach
            // everyone before the older Meklētāji introduction.
            'signature' => [
                'kicker' => 'Kas jauns',
                'title' => 'Jauns e-pasta paraksts',
                'body' => 'Visiem lietotājiem ir izveidots vienots e-pasta paraksts.',
                'bullets' => [
                    'Tas automātiski parādās katra no CRM nosūtītā e-pasta beigās.',
                    'Parakstā ir jūsu foto, vārds, amats, e-pasts un tālrunis, ja tas norādīts.',
                ],
                'link_label' => 'Rediģēt parakstu',
                'link_url' => fn (User $user): ?string => $user->isAdmin()
                    ? UserResource::getUrl('edit', ['record' => $user])
                    : ((! $user->isPhoto() && ! $user->isStudent()) ? MyProfile::getUrl() : null),
                'audience' => fn (User $user): bool => true,
            ],

            'mekletaji' => [
                'kicker' => 'Kas jauns - CRM 2.0',
                'title' => 'Meklētāji',
                'body' => 'Jauna sadaļa, kas meklē ss.lv un city24.lv portālos un savāc atrastos sludinājumus vienā vietā.',
                'bullets' => [
                    'Regulāras pārbaudes: ik pēc 6 stundām, katru dienu vai reizi nedēļā.',
                    'Atrastos objektus vari atvērt sludinājumā, atzīmēt apskatītus vai atzīmēt vairākus uzreiz.',
                    'No atrastā objekta vari pievienot CRM īpašumu ar jau aizpildītu nosaukumu, cenu, platību un atrašanās vietu.',
                ],
                'link_label' => 'Apskatīt Meklētājus',
                'link_url' => fn (User $user): string => PropertySearchResource::getUrl('index'),
                'audience' => fn (User $user): bool => ! $user->isPhoto() && ! $user->isStudent(),
            ],
        ];
    }
}
