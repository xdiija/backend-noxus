<?php

namespace App\Services;

use App\DTOs\Customer\CustomerDTO;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = Customer::query();

        if (!empty($filter)) {
            $query->where('name', 'like', "%{$filter}%");
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): Customer
    {
        return Customer::findOrFail($id);
    }

    public function create(CustomerDTO $dto): Customer
    {
        return Customer::create($dto->toArray());
    }

    public function update(string $id, CustomerDTO $dto): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->update($dto->toArray());

        return $customer;
    }

    public function changeStatus(string $id): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->status = $customer->status === 1 ? 2 : 1;
        $customer->save();

        return $customer;
    }

    public function delete(string $id): void
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
    }
}
