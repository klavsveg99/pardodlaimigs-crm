<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One user's dismissal of a one-time "new feature" popup. */
class FeatureAnnouncementView extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'feature_key', 'seen_at'];

    protected $casts = [
        'seen_at' => 'datetime',
    ];
}
