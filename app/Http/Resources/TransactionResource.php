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
            'description' => $this->description,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type
            ],
            'payments' => $this->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'account' => [
                        'id' => $payment->account->id,
                        'name' => $payment->account->name,
                    ],
                    'payment_method' => [
                        'id' => $payment->paymentMethod->id,
                        'name' => $payment->paymentMethod->name,
                    ],
                    'amount' => $payment->amount,
                    'discount' => $payment->discount,
                    'increase' => $payment->increase,
                    'due_date' => DatetHelper::toBR($payment->due_date),
                    'payment_date' => DatetHelper::toBR($payment->payment_date),
                    'status' => $payment->status,
                ];
            }),
            'created_at' => DatetHelper::toBR($this->created_at),
            'updated_at' => DatetHelper::toBR($this->updated_at),
        ];
    }
}
