<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->json('video_urls')->nullable();
            $table->softDeletes();
        });

        DB::table('knowledge_articles')
            ->whereNotNull('video_url')
            ->orderBy('id')
            ->chunkById(100, function ($articles): void {
                foreach ($articles as $article) {
                    DB::table('knowledge_articles')
                        ->where('id', $article->id)
                        ->update(['video_urls' => json_encode([$article->video_url], JSON_THROW_ON_ERROR)]);
                }
            });

        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->dropIndex('knowledge_articles_is_published_knowledge_category_id_index');
            $table->dropColumn(['video_url', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->string('video_url')->nullable();
            $table->boolean('is_published')->default(true);
        });

        DB::table('knowledge_articles')
            ->select(['id', 'video_urls'])
            ->orderBy('id')
            ->chunkById(100, function ($articles): void {
                foreach ($articles as $article) {
                    $videoUrls = json_decode((string) $article->video_urls, true);

                    if (is_array($videoUrls) && filled($videoUrls[0] ?? null)) {
                        DB::table('knowledge_articles')
                            ->where('id', $article->id)
                            ->update(['video_url' => $videoUrls[0]]);
                    }
                }
            });

        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->index(['is_published', 'knowledge_category_id']);
            $table->dropSoftDeletes();
            $table->dropColumn('video_urls');
        });
    }
};
