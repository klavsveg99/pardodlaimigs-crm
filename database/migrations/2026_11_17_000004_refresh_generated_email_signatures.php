<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Regenerate stored signatures after the signature HTML was switched to one
 * table row per value and the surrounding border was removed.
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
