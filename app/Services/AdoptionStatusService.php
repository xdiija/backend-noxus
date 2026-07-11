<?php

namespace App\Services;

use App\Models\AdoptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdoptionStatusService
{
    /**
     * System defaults (organization_id NULL) plus this org's own statuses.
     */
    public function list(): Collection
    {
        return $this->readable()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Readable rows: the shared global entries (organization_id NULL) plus the
     * org's own, mirroring the animal-statuses lookup (ADR-0004/0008).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return AdoptionStatus::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
