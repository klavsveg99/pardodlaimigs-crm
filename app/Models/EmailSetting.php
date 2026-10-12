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
            'task_notification' => [
                'subject' => 'Jauns uzdevums',
                'body' => 'Jums piešķirts jauns uzdevums.',
            ],
        ];
    }
}
