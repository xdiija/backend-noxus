<?php

namespace App\Services;

use App\DTOs\Volunteer\VolunteerDTO;
use App\Models\Volunteer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VolunteerService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->scoped();

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('email', 'like', "%{$filter}%")
                    ->orWhere('role', 'like', "%{$filter}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function find(string $id): Volunteer
    {
        return $this->scoped()->findOrFail($id);
    }

    public function create(VolunteerDTO $dto): Volunteer
    {
        // organization_id comes from the authenticated user, never from input.
        return Volunteer::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);
    }

    public function update(string $id, VolunteerDTO $dto): Volunteer
    {
        $volunteer = $this->scoped()->findOrFail($id);
        $volunteer->update($dto->toArray());

        return $volunteer;
    }

    public function changeStatus(string $id): Volunteer
    {
        $volunteer = $this->scoped()->findOrFail($id);
        $volunteer->status = $volunteer->status === 1 ? 0 : 1;
        $volunteer->save();

        return $volunteer;
    }

    public function delete(string $id): void
    {
        $volunteer = $this->scoped()->findOrFail($id);
        $volunteer->delete();
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's volunteers.
     */
    private function scoped(): Builder
    {
        return Volunteer::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
