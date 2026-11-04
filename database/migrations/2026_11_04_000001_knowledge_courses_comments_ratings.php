<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_categories', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
        });

        Schema::create('knowledge_course_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_category_id')->constrained('knowledge_categories')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();
            $table->unique(['knowledge_category_id', 'assigned_user_id'], 'knowledge_course_assignments_unique');
            $table->index(['assigned_user_id', 'completed_at']);
        });

        Schema::create('knowledge_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('knowledge_article_id')->nullable()->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('knowledge_category_id')->nullable()->constrained('knowledge_categories')->cascadeOnDelete();
            $table->unsignedInteger('step_index')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->index(['knowledge_article_id', 'step_index']);
            $table->index('knowledge_category_id');
        });

        Schema::table('knowledge_assignments', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating')->nullable()->after('completed_at');
        });

        Schema::table('knowledge_completions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_completions', function (Blueprint $table): void {
            $table->dropColumn('rating');
        });

        Schema::table('knowledge_assignments', function (Blueprint $table): void {
            $table->dropColumn('rating');
        });

        Schema::dropIfExists('knowledge_comments');
        Schema::dropIfExists('knowledge_course_assignments');

        Schema::table('knowledge_categories', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
