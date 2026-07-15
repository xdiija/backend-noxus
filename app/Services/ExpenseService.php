<?php

namespace App\Services;

use App\DTOs\Expense\ExpenseDTO;
use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ExpenseService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->scoped()->with('category');

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('description', 'like', "%{$filter}%")
                    ->orWhereHas('category', function (Builder $category) use ($filter) {
                        $category->where('name', 'like', "%{$filter}%");
                    });
            });
        }

        return $query->latest('expense_date')->latest('id')->paginate($perPage);
    }

    public function find(string $id): Expense
    {
        return $this->scoped()->with('category')->findOrFail($id);
    }

    public function create(ExpenseDTO $dto): Expense
    {
        // organization_id comes from the authenticated user, never from input.
        $expense = Expense::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);

        return $expense->load('category');
    }

    public function update(string $id, ExpenseDTO $dto): Expense
    {
        $expense = $this->scoped()->findOrFail($id);
        $expense->update($dto->toArray());

        return $expense->load('category');
    }

    public function togglePaid(string $id): Expense
    {
        $expense = $this->scoped()->findOrFail($id);
        $expense->paid = ! $expense->paid;
        $expense->save();

        return $expense;
    }

    public function delete(string $id): void
    {
        $expense = $this->scoped()->findOrFail($id);
        $expense->delete();
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's expenses.
     */
    private function scoped(): Builder
    {
        return Expense::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
