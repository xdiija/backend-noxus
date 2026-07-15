<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an append-only `audit_logs` row on created/updated/deleted for the
 * model using this trait (soft deletes fire `deleted` too). Only actions by
 * an authenticated user are audited — seeders and console runs stay silent —
 * so the trail answers "who did what" rather than logging machine activity.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn (Model $model) => static::recordAudit('created', $model));
        static::updated(fn (Model $model) => static::recordAudit('updated', $model));
        static::deleted(fn (Model $model) => static::recordAudit('deleted', $model));
    }

    protected static function recordAudit(string $action, Model $model): void
    {
        $user = auth()->user();

        // audit_logs.organization_id is required: prefer the affected row's
        // org, falling back to the actor's (Noxus users may have none).
        $organizationId = $model->organization_id ?? $user?->organization_id;

        if (! $user || ! $organizationId) {
            return;
        }

        AuditLog::create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'action' => $action,
            'entity' => class_basename($model),
            'entity_id' => $model->getKey(),
        ]);
    }
}
