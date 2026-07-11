<?php

namespace App\Services;

use App\DTOs\Adopter\AdopterDTO;
use App\Models\Adopter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdopterService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->scoped();

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('cpf', 'like', "%{$filter}%")
                    ->orWhere('email', 'like', "%{$filter}%")
                    ->orWhere('city', 'like', "%{$filter}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function find(string $id): Adopter
    {
        return $this->scoped()->findOrFail($id);
    }

    public function create(AdopterDTO $dto): Adopter
    {
        // organization_id comes from the authenticated user, never from input.
        return Adopter::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);
    }

    public function update(string $id, AdopterDTO $dto): Adopter
    {
        $adopter = $this->scoped()->findOrFail($id);
        $adopter->update($dto->toArray());

        return $adopter;
    }

    public function delete(string $id): void
    {
        $adopter = $this->scoped()->findOrFail($id);
        $adopter->delete();
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's adopters.
     */
    private function scoped(): Builder
    {
        return Adopter::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
