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
            $status = AnimalStatus::create($dto->toArray() + [
                'organization_id' => $this->organizationId(),
            ]);

            $this->enforceSingleDefault($status);

            return $status;
        });
    }

    public function update(string $id, AnimalStatusDTO $dto): AnimalStatus
    {
        return DB::transaction(function () use ($id, $dto) {
            $status = $this->owned()->findOrFail($id);
            $status->update($dto->toArray());

            $this->enforceSingleDefault($status);

            return $status;
        });
    }

    public function delete(string $id): void
    {
        $status = $this->owned()->findOrFail($id);
        $status->delete();
    }

    /**
     * At most one org-owned status may be the default. When one is marked, clear
     * the flag on the org's other statuses. System defaults are untouched.
     */
    private function enforceSingleDefault(AnimalStatus $status): void
    {
        if (! $status->is_default) {
            return;
        }

        $this->owned()
            ->where('id', '!=', $status->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Readable rows: system defaults + the org's own entries (ADR-0004).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return AnimalStatus::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    /**
     * Writable rows: only the org's own custom entries. System defaults
     * (organization_id NULL) are shared and can't be edited or deleted.
     */
    private function owned(): Builder
    {
        return AnimalStatus::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
