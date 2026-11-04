<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_article_knowledge_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('knowledge_category_id')->constrained('knowledge_categories')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['knowledge_article_id', 'knowledge_category_id'], 'knowledge_article_category_unique');
            $table->index(['knowledge_category_id', 'sort_order']);
        });

        $categoryIds = DB::table('knowledge_articles')
            ->whereNotNull('knowledge_category_id')
            ->distinct()
            ->pluck('knowledge_category_id');

        foreach ($categoryIds as $categoryId) {
            $articleIds = DB::table('knowledge_articles')
                ->where('knowledge_category_id', $categoryId)
                ->orderBy('id')
                ->pluck('id');

            $order = 1;

            foreach ($articleIds as $articleId) {
                DB::table('knowledge_article_knowledge_category')->insert([
                    'knowledge_article_id' => $articleId,
                    'knowledge_category_id' => $categoryId,
                    'sort_order' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('knowledge_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->foreignId('knowledge_category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
        });

        $pivots = DB::table('knowledge_article_knowledge_category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('knowledge_article_id');

        foreach ($pivots as $articleId => $rows) {
            DB::table('knowledge_articles')
                ->where('id', $articleId)
                ->update(['knowledge_category_id' => $rows->first()->knowledge_category_id]);
        }

        Schema::dropIfExists('knowledge_article_knowledge_category');
    }
};
