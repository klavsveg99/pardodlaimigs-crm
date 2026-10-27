<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeCompletion extends Model
{
    protected $fillable = [
        'knowledge_article_id', 'user_id', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
