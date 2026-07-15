<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new ExpenseCategoryResource($this->whenLoaded('category')),
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'expense_date' => DateHelper::toBR($this->expense_date),
            'paid' => $this->paid,
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
