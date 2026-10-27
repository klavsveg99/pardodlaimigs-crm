<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->after('type')->index();
        });

        Schema::create('knowledge_completions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['knowledge_article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_completions');

        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->dropColumn('is_public');
        });
    }
};
