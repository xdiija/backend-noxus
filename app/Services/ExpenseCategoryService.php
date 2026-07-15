<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ExpenseCategoryService
{
    /**
     * System defaults (organization_id NULL) plus this org's own categories.
     */
    public function list(): Collection
    {
        return $this->readable()->orderBy('name')->get();
    }

    /**
     * Readable rows: the shared global entries (organization_id NULL) plus the
     * org's own, mirroring the adoption-statuses lookup (ADR-0004).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return ExpenseCategory::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
