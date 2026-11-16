<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * One-time replacement of every user's free-form signature with the standard
 * generated one: photo, name, role, e-mail and phone.
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
        // Generated signatures are not restored to their previous values.
    }
};
