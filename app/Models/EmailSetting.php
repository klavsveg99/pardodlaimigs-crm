<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Administratora rediģējamie automātisko e-pastu teksti (viena rinda).
 * Noklusējumi: apsveikumi no config('crm.greetings'), uzdevuma paziņojums šeit.
 */
class EmailSetting extends Model
{
    /** Frontend listing order choices shown in Settings -> Īpašumi. */
    public const PROPERTY_SORTS = [
        'sort_order' => 'Pēc īpašumu tabulas secības',
        'featured_sort_order' => 'Ar zvaigzni pirmie, pēc tam īpašumu tabulas secība',
        'random' => 'Random',
        'featured_random' => 'Ar zvaigzni pirmie, pēc tam random',
    ];

    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['data' => self::defaults()]);
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'greetings' => config('crm.greetings', []),
            'offers' => [
                'auto_send' => false,
                'subject' => 'Sadarbības piedāvājums',
                'body' => '<p>Sveiki,</p>'
                    .'<p>Vēlamies piedāvāt sadarbību Jūsu īpašuma pārdošanā. Mums ir klienti, kas meklē šādu īpašumu, tāpēc varam palīdzēt to pārdot ātrāk un izdevīgāk.</p>'
                    .'<p>Ja interesē, lūdzu, atbildiet uz šo e-pastu vai sazinieties ar mums.</p>'
                    .'<p>Ar cieņu,<br>Pārdod Laimīgs</p>',
            ],
            'properties' => [
                'frontend_sort' => 'featured_sort_order',
            ],
        ];
    }

    /**
     * Offer-email settings merged with defaults, so a settings row saved
     * before this section existed still has a usable subject and body.
     *
     * @return array{auto_send: bool, subject: string, body: string}
     */
    public static function offerConfiguration(): array
    {
        $defaults = self::defaults()['offers'];
        $data = self::current()->data ?? [];
        $stored = is_array($data) ? ($data['offers'] ?? []) : [];

        if (! is_array($stored)) {
            $stored = [];
        }

        $merged = array_replace($defaults, $stored);

        return [
            'auto_send' => (bool) ($merged['auto_send'] ?? false),
            'subject' => filled($merged['subject'] ?? null) ? (string) $merged['subject'] : $defaults['subject'],
            'body' => filled($merged['body'] ?? null) ? (string) $merged['body'] : $defaults['body'],
        ];
    }

    public static function offersAutoSend(): bool
    {
        return self::offerConfiguration()['auto_send'];
    }

    public static function offerSubject(): string
    {
        return self::offerConfiguration()['subject'];
    }

    public static function offerBody(): string
    {
        return self::offerConfiguration()['body'];
    }

    /** The validated sort mode used by the public property feed. */
    public static function frontendPropertySort(): string
    {
        $sort = (string) (self::current()->data['properties']['frontend_sort'] ?? 'featured_sort_order');

        return array_key_exists($sort, self::PROPERTY_SORTS) ? $sort : 'featured_sort_order';
    }
}
