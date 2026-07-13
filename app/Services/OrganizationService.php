<?php

namespace App\Services;

use App\DTOs\Organization\OrganizationDTO;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Tenant management — deliberately NOT organization-scoped: this resource is
 * reachable only by Noxus users (the /organizations menu is exclusive_noxus
 * and no role gets a role_menu grant, so check.permissions blocks everyone
 * Noxus doesn't bypass).
 */
class OrganizationService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = Organization::query()->withCount('users');

        if (! empty($filter)) {
            $query->where(function (Builder $q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('legal_name', 'like', "%{$filter}%")
                    ->orWhere('tax_id', 'like', "%{$filter}%")
                    ->orWhere('city', 'like', "%{$filter}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function find(string $id): Organization
    {
        return Organization::query()->withCount('users')->findOrFail($id);
    }

    public function create(OrganizationDTO $dto): Organization
    {
        return Organization::create($dto->toArray());
    }

    public function update(string $id, OrganizationDTO $dto): Organization
    {
        $organization = Organization::query()->findOrFail($id);
        $organization->update($dto->toArray());

        return $organization->loadCount('users');
    }

    public function delete(string $id): void
    {
        $organization = Organization::query()->findOrFail($id);

        // A tenant with users would leave them logging into a dead org —
        // reassign or remove the users first.
        if ($organization->users()->exists()) {
            throw ValidationException::withMessages([
                'organization' => 'A organização possui usuários vinculados e não pode ser excluída.',
            ]);
        }

        $organization->delete();
    }
}
