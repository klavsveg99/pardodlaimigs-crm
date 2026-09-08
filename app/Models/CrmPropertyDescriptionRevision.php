<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmPropertyDescriptionRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'crm_property_id',
        'user_id',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(CrmProperty::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
