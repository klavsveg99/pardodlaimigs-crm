<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Latvijas kalendārs automātiskajiem apsveikumiem: valsts svētku datumi
 * (arī kustīgie, piem. Lieldienas un Mātes diena) un vārdadienas.
 */
class LatvianCalendar
{
    /**
     * Svētku datums konkrētajā gadā no config ieraksta.
     * Atbalsta 'date' => 'DD.MM' un 'rule' => easter | easter-2 | easter+1 | mothers_day.
     *
     * @param  array<string, mixed>  $holiday
     */
    public static function holidayDate(array $holiday, int $year): ?Carbon
    {
        if (isset($holiday['date'])) {
            $parts = explode('.', (string) $holiday['date']);

            if (count($parts) !== 2) {
                return null;
            }

            [$day, $month] = array_map('intval', $parts);

            return checkdate($month, $day, $year)
                ? Carbon::create($year, $month, $day)->startOfDay()
                : null;
        }

        return match ($holiday['rule'] ?? null) {
            'easter' => self::easterSunday($year),
            'easter-2' => self::easterSunday($year)->subDays(2),
            'easter+1' => self::easterSunday($year)->addDay(),
            'mothers_day' => self::mothersDay($year),
            default => null,
        };
    }

    /**
     * Vārdadienas konkrētajā datumā (no config/name_days.php).
     *
     * @return array<int, string>
     */
    public static function nameDays(Carbon $date): array
    {
        return (array) config('name_days.'.$date->format('m-d'), []);
    }

    /** Lieldienu svētdiena (Anonymous Gregorian algorithm). */
    public static function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }

    /** Mātes diena: otrā maija svētdiena. */
    public static function mothersDay(int $year): Carbon
    {
        $may1 = Carbon::create($year, 5, 1)->startOfDay();

        return $may1->addDays(((7 - $may1->dayOfWeek) % 7) + 7);
    }
}
