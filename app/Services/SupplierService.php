<?php

namespace App\Services;

use App\DTOs\Supplier\SupplierDTO;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = Supplier::query();

        if (!empty($filter)) {
            $query->where('nome_fantasia', 'like', "%{$filter}%")
                  ->orWhere('razao_social', 'like', "%{$filter}%")
                  ->orWhere('cnpj', 'like', "%{$filter}%");
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): Supplier
    {
        return Supplier::findOrFail($id);
    }

    public function create(SupplierDTO $dto): Supplier
    {
        return Supplier::create($dto->toArray());
    }

    public function update(string $id, SupplierDTO $dto): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($dto->toArray());

        return $supplier;
    }

    public function changeStatus(string $id): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->status = $supplier->status === 1 ? 0 : 1;
        $supplier->save();

        return $supplier;
    }

    public function delete(string $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
    }
}
