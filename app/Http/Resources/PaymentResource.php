<?php

namespace App\Http\Resources;

use App\Helpers\DatetHelper;
use App\Helpers\Money;
use App\Helpers\StatusHelper;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction' => $this->whenLoaded('transaction', function () {
                return [
                    'id' => $this->transaction_id,
                    'description' => $this->transaction->description,
                    'type' => optional($this->transaction->category)->type,
                    'category_name' => optional($this->transaction->category)->name,
                ];
            }),
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name,
                ];
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return [
                    'id' => $this->paymentMethod->id,
                    'name' => $this->paymentMethod->name,
                ];
            }),
            'amount' => $this->amount,
            'discount' => $this->discount,
            'increase' => $this->increase,
            'due_date' => DatetHelper::toBR($this->due_date),
            'payment_date' => DatetHelper::toBR($this->payment_date),
            'status' => [
                'id' => $this->status,
                'name' => Payment::getStatusName($this->status),
            ],
            'created_at' => DatetHelper::toBR($this->created_at),
            'updated_at' => DatetHelper::toBR($this->updated_at),
        ];
    }
}
