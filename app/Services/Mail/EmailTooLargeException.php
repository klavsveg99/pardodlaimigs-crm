<?php

declare(strict_types=1);

namespace App\Services\Mail;

use RuntimeException;

class EmailTooLargeException extends RuntimeException
{
    public function __construct(public readonly int $sizeBytes)
    {
        parent::__construct('Pielikumi pārsniedz 18 MB. Sūtiet mazāk failu vienā e-pastā.');
    }

    public function userMessage(): string
    {
        return 'Pielikumi pārsniedz 18 MB (izvēlēti '
            .sprintf('%.1f', $this->sizeBytes / (1024 * 1024)).' MB). Sūtiet mazāk failu vienā e-pastā.';
    }
}
