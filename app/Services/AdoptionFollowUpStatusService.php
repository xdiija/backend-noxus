<?php

namespace App\Services;

use App\Models\AdoptionFollowUpStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdoptionFollowUpStatusService
{
    /**
     * System defaults (organization_id NULL) plus this org's own statuses.
     */
    public function list(): Collection
    {
        return $this->readable()->orderBy('name')->get();
    }

    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return AdoptionFollowUpStatus::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
