<?php

namespace App\Http\Resources;

use App\Helpers\DatetHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'due_date' => DatetHelper::toBR($this->due_date),
            'payment_date' => DatetHelper::toBR($this->payment_date),
            'description' => $this->description,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ],
            'created_at' => DatetHelper::toBR($this->created_at),
            'updated_at' => DatetHelper::toBR($this->updated_at),
        ];
    }
}
