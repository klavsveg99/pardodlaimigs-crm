<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeCourseAssignment extends Model
{
    protected $fillable = [
        'knowledge_category_id', 'assigned_user_id', 'assigned_by_user_id',
        'due_at', 'completed_at', 'rating',
    ];

    protected $casts = [
        'due_at' => 'date',
        'completed_at' => 'datetime',
        'rating' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
