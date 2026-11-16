<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Rebuild signatures as a one-row, two-cell table with paragraphs. ProseMirror
 * cannot edit the previous rowspan table reliably and froze on clicks.
 */
return new class extends Migration
{
    public function up(): void
    {
        User::withTrashed()->orderBy('id')->each(function (User $user): void {
            $user->forceFill([
                'email_signature' => $user->buildEmailSignature(),
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        // Previous generated HTML is not restored.
    }
};
