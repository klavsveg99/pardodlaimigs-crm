<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

/**
 * "Atbildīgais aģents" / "Aģents" lauka noteikumi: aģentu drīkst izvēlēties
 * tikai administrators. Skata lapā lauks paliek redzams (tikai lasāms) arī
 * pārējiem, un tabulās aģents tiek rādīts vienmēr.
 */
final class AgentField
{
    /** Redzams administratoram (izvēlei) vai jebkuram skata režīmā. */
    public static function visible(): Closure
    {
        return fn (string $operation): bool => $operation === 'view'
            || (auth()->user()?->can('manage') ?? false);
    }

    /** Skata režīmā lauks ir tikai lasāms. */
    public static function disabled(): Closure
    {
        return fn (string $operation): bool => $operation === 'view';
    }
}
