<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Phone validation. Accepts:
 *  - clean E.164 numbers stored by the PhoneInput JS (+37120000000)
 *  - legacy values without a dial code (e.g. "27777777") so old rows stay editable
 */
class Phone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        $digits = preg_replace('/[\s().\/-]+/', '', $value) ?? '';

        if (preg_match('/^\+[1-9]\d{1,14}$/', $digits)) {
            return;
        }

        if (preg_match('/^\d{6,14}$/', $digits)) {
            return;
        }

        $fail('Nederīgs tālruņa numurs. Izmantojiet formātu, piemēram, +371 20000000.')->translate();
    }
}
