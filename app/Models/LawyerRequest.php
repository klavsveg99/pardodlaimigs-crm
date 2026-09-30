<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Juristam nosūtīts dokumenta pieprasījums (vēsture). */
class LawyerRequest extends Model
{
    protected $fillable = [
        'crm_property_id', 'izpilditajs_id', 'document_type', 'recipient_email',
        'subject', 'payload', 'status', 'sent_by_user_id', 'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(CrmProperty::class, 'crm_property_id');
    }

    public function jurist(): BelongsTo
    {
        return $this->belongsTo(Izpilditajs::class, 'izpilditajs_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function getDocumentLabelAttribute(): string
    {
        return config("crm.lawyer.documents.{$this->document_type}.label", $this->document_type);
    }
}
