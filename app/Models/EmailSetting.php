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
            'properties' => [
                'frontend_sort' => 'featured_sort_order',
            ],
        ];
    }

    /** The validated sort mode used by the public property feed. */
    public static function frontendPropertySort(): string
    {
        $sort = (string) (self::current()->data['properties']['frontend_sort'] ?? 'featured_sort_order');

        return array_key_exists($sort, self::PROPERTY_SORTS) ? $sort : 'featured_sort_order';
    }
}
