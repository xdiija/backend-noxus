<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read side of the audit trail. Rows are written by the Auditable model
 * concern; this service only lists them (append-only — no create/update/
 * delete surface anywhere).
 */
class AuditLogService
{
    public function list(
        int $perPage = 10,
        string $filter = '',
        ?string $entity = null,
        ?int $entityId = null,
        ?string $action = null,
    ): LengthAwarePaginator {
        $query = $this->scoped()->with('user:id,name');

        if ($entity) {
            $query->where('entity', $entity);
        }

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('entity', 'like', "%{$filter}%")
                    ->orWhereHas('user', function (Builder $user) use ($filter) {
                        $user->where('name', 'like', "%{$filter}%");
                    });
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's trail.
     */
    private function scoped(): Builder
    {
        return AuditLog::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
