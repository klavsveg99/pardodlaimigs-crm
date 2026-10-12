<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Īskodi ({name}, {task}, …) automātisko e-pastu sagatavēs. Sagataves glabā
 * EmailSetting; sūtītāji aizstāj īskodus ar konkrētā e-pasta datiem.
 */
class EmailShortcodes
{
    /** Apsveikumu īskodi. */
    public const GREETING_LABELS = [
        'name' => 'klienta vārds',
    ];

    /** Uzdevuma paziņojuma īskodi. */
    public const TASK_LABELS = [
        'task' => 'uzdevuma nosaukums',
        'due' => 'termiņš',
        'description' => 'uzdevuma apraksts',
        'agent' => 'aģents',
        'executor' => 'izpildītājs',
        'client' => 'klients',
        'property' => 'īpašums',
    ];

    /** Kavētā uzdevuma atgādinājuma īskodi. */
    public const OVERDUE_LABELS = [
        'task' => 'uzdevuma nosaukums',
        'due' => 'termiņš',
        'client' => 'klients',
        'property' => 'īpašums',
    ];

    /**
     * Aizstāj {key} ar vērtību. Jau aizstātās vērtības atkārtoti netiek
     * skenētas (strtr), tāpēc lietotāja dati nevar injicēt īskodus.
     *
     * @param  array<string, string|null>  $values
     */
    public static function replace(string $template, array $values): string
    {
        $map = [];

        foreach ($values as $key => $value) {
            $map['{'.$key.'}'] = (string) ($value ?? '');
        }

        return strtr($template, $map);
    }

    /**
     * Cilvēkam lasāms īskodu saraksts palīdzības tekstam: "{name} - klienta vārds, …".
     *
     * @param  array<string, string>  $labels
     */
    public static function hint(array $labels): string
    {
        $parts = [];

        foreach ($labels as $key => $label) {
            $parts[] = '{'.$key.'} - '.$label;
        }

        return 'Pieejamie kodi: '.implode(', ', $parts).'.';
    }
}
