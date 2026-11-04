<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_comments', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_comments', function (Blueprint $table): void {
            $table->dropIndex(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
