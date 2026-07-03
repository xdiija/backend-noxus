<?php

namespace App\Services;

use App\DTOs\Supplier\SupplierDTO;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupplierService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->scoped();

        if (!empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('nome_fantasia', 'like', "%{$filter}%")
                  ->orWhere('razao_social', 'like', "%{$filter}%")
                  ->orWhere('cnpj', 'like', "%{$filter}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): Supplier
    {
        return $this->scoped()->findOrFail($id);
    }

    public function create(SupplierDTO $dto): Supplier
    {
        // organization_id comes from the authenticated user, never from input.
        return Supplier::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);
    }

    public function update(string $id, SupplierDTO $dto): Supplier
    {
        $supplier = $this->scoped()->findOrFail($id);
        $supplier->update($dto->toArray());

        return $supplier;
    }

    public function changeStatus(string $id): Supplier
    {
        $supplier = $this->scoped()->findOrFail($id);
        $supplier->status = $supplier->status === 1 ? 0 : 1;
        $supplier->save();

        return $supplier;
    }

    public function delete(string $id): void
    {
        $supplier = $this->scoped()->findOrFail($id);
        $supplier->delete();
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's suppliers.
     */
    private function scoped(): Builder
    {
        return Supplier::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
