<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'knowledge_category_id');
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(KnowledgeCourseAssignment::class, 'knowledge_category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(KnowledgeComment::class, 'knowledge_category_id');
    }
}
