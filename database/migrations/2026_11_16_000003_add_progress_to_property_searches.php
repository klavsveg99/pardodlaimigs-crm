<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_searches', function (Blueprint $table): void {
            if (! Schema::hasColumn('property_searches', 'progress_status')) {
                $table->string('progress_status', 20)->default('idle')->after('last_error')->index();
            }
            if (! Schema::hasColumn('property_searches', 'progress_current')) {
                $table->unsignedInteger('progress_current')->default(0)->after('progress_status');
            }
            if (! Schema::hasColumn('property_searches', 'progress_total')) {
                $table->unsignedInteger('progress_total')->default(0)->after('progress_current');
            }
            if (! Schema::hasColumn('property_searches', 'progress_found')) {
                $table->unsignedInteger('progress_found')->default(0)->after('progress_total');
            }
            if (! Schema::hasColumn('property_searches', 'progress_message')) {
                $table->string('progress_message')->nullable()->after('progress_found');
            }
            if (! Schema::hasColumn('property_searches', 'progress_started_at')) {
                $table->timestamp('progress_started_at')->nullable()->after('progress_message');
            }
            if (! Schema::hasColumn('property_searches', 'progress_finished_at')) {
                $table->timestamp('progress_finished_at')->nullable()->after('progress_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_searches', function (Blueprint $table): void {
            foreach ([
                'progress_finished_at',
                'progress_started_at',
                'progress_message',
                'progress_found',
                'progress_total',
                'progress_current',
                'progress_status',
            ] as $column) {
                if (Schema::hasColumn('property_searches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
