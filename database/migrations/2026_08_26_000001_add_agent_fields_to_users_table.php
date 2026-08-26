<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('role');
            $table->string('position', 255)->nullable()->after('phone');
            $table->text('description')->nullable()->after('position');
            $table->string('avatar_path', 500)->nullable()->after('description');
            $table->string('facebook_url', 500)->nullable()->after('avatar_path');
            $table->string('instagram_url', 500)->nullable()->after('facebook_url');
            $table->string('linkedin_url', 500)->nullable()->after('instagram_url');
            $table->string('website_url', 500)->nullable()->after('linkedin_url');
            $table->string('office_address', 500)->nullable()->after('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'position', 'description', 'avatar_path', 'facebook_url', 'instagram_url', 'linkedin_url', 'website_url', 'office_address']);
        });
    }
};
