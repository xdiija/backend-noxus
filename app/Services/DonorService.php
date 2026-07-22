<?php

namespace App\Services;

use App\DTOs\Donor\DonorDTO;
use App\Models\Donor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DonorService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->scoped();

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('email', 'like', "%{$filter}%")
                    ->orWhere('phone', 'like', "%{$filter}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Slim {id, name} rows for host-gated select fields (donation form), so
     * consumers don't need this resource's own menu grant.
     */
    public function options(string $filter = '', int $limit = 20): Collection
    {
        return $this->scoped()
            ->when($filter !== '', fn (Builder $q) => $q->where('name', 'like', "%{$filter}%"))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name]);
    }

    public function find(string $id): Donor
    {
        return $this->scoped()->findOrFail($id);
    }

    public function create(DonorDTO $dto): Donor
    {
        // organization_id comes from the authenticated user, never from input.
        return Donor::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);
    }

    public function update(string $id, DonorDTO $dto): Donor
    {
        $donor = $this->scoped()->findOrFail($id);
        $donor->update($dto->toArray());

        return $donor;
    }

    public function delete(string $id): void
    {
        $donor = $this->scoped()->findOrFail($id);
        $donor->delete();
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's donors.
     */
    private function scoped(): Builder
    {
        return Donor::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
