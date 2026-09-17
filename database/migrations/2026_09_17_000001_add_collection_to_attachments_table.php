<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attachments are split into collections: the property gallery images
     * ("gallery") vs. documents ("documents"). Existing rows stay in the
     * gallery collection, which is also the default for client attachments.
     */
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->string('collection')->default('gallery')->after('attachable_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropColumn('collection');
        });
    }
};
