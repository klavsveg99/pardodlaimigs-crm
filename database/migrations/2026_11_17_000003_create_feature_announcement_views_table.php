<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feature_announcement_views')) {
            Schema::create('feature_announcement_views', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('feature_key', 80);
                $table->timestamp('seen_at')->nullable();
                $table->unique(['user_id', 'feature_key']);
            });
        }

        // Preserve the two one-time popups that existed before this generic
        // store, so nobody sees the same announcement twice.
        $this->importFlag('mekletaji_intro_seen_at', 'mekletaji');
        $this->importFlag('signature_intro_seen_at', 'signature');
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_announcement_views');
    }

    private function importFlag(string $column, string $feature): void
    {
        if (! Schema::hasColumn('users', $column)) {
            return;
        }

        DB::table('users')
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($column, $feature): void {
                $rows = [];

                foreach ($users as $user) {
                    $rows[] = [
                        'user_id' => $user->id,
                        'feature_key' => $feature,
                        'seen_at' => $user->{$column},
                    ];
                }

                if ($rows !== []) {
                    DB::table('feature_announcement_views')->insertOrIgnore($rows);
                }
            });
    }
};
