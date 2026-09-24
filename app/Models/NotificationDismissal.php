<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lietotāja aizvērts paziņojums (atslēga + laiks). */
class NotificationDismissal extends Model
{
    protected $fillable = ['user_id', 'key', 'dismissed_at'];

    protected $casts = [
        'dismissed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
