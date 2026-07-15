<?php

namespace App\DTOs\Expense;

use App\Http\Requests\Expense\StoreUpdateExpenseRequest;

class ExpenseDTO
{
    public function __construct(
        public readonly ?int $expenseCategoryId,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $expenseDate,
        public readonly bool $paid,
    ) {}

    public static function fromRequest(StoreUpdateExpenseRequest $request): self
    {
        $data = $request->validated();

        return new self(
            expenseCategoryId: isset($data['expense_category_id']) ? (int) $data['expense_category_id'] : null,
            description: $data['description'],
            amount: (float) $data['amount'],
            expenseDate: $data['expense_date'],
            paid: (bool) ($data['paid'] ?? false),
        );
    }

    /**
     * Model-ready payload for `expenses` (no organization_id — the Service
     * sets it from the authenticated user).
     */
    public function toArray(): array
    {
        return [
            'expense_category_id' => $this->expenseCategoryId,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expenseDate,
            'paid' => $this->paid,
        ];
    }
}
