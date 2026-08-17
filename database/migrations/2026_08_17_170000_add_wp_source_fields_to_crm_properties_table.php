<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
            $table->json('image_urls')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
            $table->dropColumn(['wp_post_id', 'image_urls']);
        });
    }
};
