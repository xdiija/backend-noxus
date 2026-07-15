<?php

namespace App\Services;

use App\DTOs\Donor\DonorDTO;
use App\Models\Donor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
