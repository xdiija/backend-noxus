<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only activity trail (see data-model/entities/system.md). Rows are
 * written by the Auditable model concern; never update or delete them. No
 * FK to the target row by design — the target may be soft-deleted or gone,
 * so `entity`/`entity_id` are a loose reference.
 */
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'entity',
        'entity_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
