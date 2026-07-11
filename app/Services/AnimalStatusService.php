<?php

namespace App\Services;

use App\DTOs\AnimalStatus\AnimalStatusDTO;
use App\Models\AnimalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AnimalStatusService
{
    /**
     * System defaults (organization_id NULL) plus this org's own statuses.
     */
    public function list(): Collection
    {
        return $this->readable()->orderBy('sort_order')->orderBy('name')->get();
    }

    public function find(string $id): AnimalStatus
    {
        return $this->readable()->findOrFail($id);
    }

    public function create(AnimalStatusDTO $dto): AnimalStatus
    {
        return DB::transaction(function () use ($dto) {
            // Noxus-managed global reference (ADR-0008): no organization_id.
            $status = AnimalStatus::create($dto->toArray() + [
                'organization_id' => null,
            ]);

            $this->enforceSingleDefault($status);

            return $status;
        });
    }

    public function update(string $id, AnimalStatusDTO $dto): AnimalStatus
    {
        return DB::transaction(function () use ($id, $dto) {
            $status = $this->writable()->findOrFail($id);
            $status->update($dto->toArray());

            $this->enforceSingleDefault($status);

            return $status;
        });
    }

    public function delete(string $id): void
    {
        $status = $this->writable()->findOrFail($id);
        $status->delete();
    }

    /**
     * At most one global status may be the default. When one is marked, clear
     * the flag on the other global statuses.
     */
    private function enforceSingleDefault(AnimalStatus $status): void
    {
        if (! $status->is_default) {
            return;
        }

        $this->writable()
            ->where('id', '!=', $status->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Readable rows: the shared global entries (organization_id NULL). The
     * org-scoped predicate is retained defensively (ADR-0004 history).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return AnimalStatus::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    /**
     * Writable rows: the shared global entries, managed directly by Noxus
     * (ADR-0008).
     */
    private function writable(): Builder
    {
        return AnimalStatus::query()->whereNull('organization_id');
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
