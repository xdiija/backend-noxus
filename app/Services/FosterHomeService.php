<?php

namespace App\Services;

use App\DTOs\FosterHome\FosterHomeDTO;
use App\Models\FosterHome;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FosterHomeService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->scoped()->withCount('openPlacements');

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('responsible_name', 'like', "%{$filter}%")
                    ->orWhere('address', 'like', "%{$filter}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Slim {id, name} rows (name = responsible_name) for host-gated select
     * fields (document owner search), so consumers don't need this
     * resource's own menu grant.
     */
    public function options(string $filter = '', int $limit = 20): Collection
    {
        return $this->scoped()
            ->when($filter !== '', fn (Builder $q) => $q->where('responsible_name', 'like', "%{$filter}%"))
            ->orderBy('responsible_name')
            ->limit($limit)
            ->get(['id', 'responsible_name'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->responsible_name]);
    }

    public function find(string $id): FosterHome
    {
        return $this->scoped()->withCount('openPlacements')->findOrFail($id);
    }

    public function create(FosterHomeDTO $dto): FosterHome
    {
        // organization_id comes from the authenticated user, never from input.
        return FosterHome::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);
    }

    public function update(string $id, FosterHomeDTO $dto): FosterHome
    {
        $fosterHome = $this->scoped()->findOrFail($id);
        $fosterHome->update($dto->toArray());

        return $fosterHome;
    }

    public function delete(string $id): void
    {
        $fosterHome = $this->scoped()->findOrFail($id);
        $fosterHome->delete();
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's foster homes.
     */
    private function scoped(): Builder
    {
        return FosterHome::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
