<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Izpilditajs;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['clients', 'users', 'izpilditajs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('slug')->nullable()->index()->after('name');
            });
        }

        foreach (Client::withoutGlobalScopes()->get() as $client) {
            if (blank($client->name)) {
                continue;
            }
            $client->slug = Client::generateUniqueSlug((string) $client->name, ignoreKey: $client->id);
            $client->newQuery()->withoutGlobalScopes()->whereKey($client->id)->update(['slug' => $client->slug]);
        }

        foreach (User::query()->get() as $user) {
            $user->newQuery()->whereKey($user->id)->update([
                'slug' => User::generateUniqueSlug((string) $user->name, ignoreKey: $user->id),
            ]);
        }

        foreach (Izpilditajs::query()->get() as $izp) {
            $izp->newQuery()->whereKey($izp->id)->update([
                'slug' => Izpilditajs::generateUniqueSlug((string) $izp->name, ignoreKey: $izp->id),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['clients', 'users', 'izpilditajs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('slug');
            });
        }
    }
};
