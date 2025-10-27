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
            'payment_type' => $this->payment_type,
            'payment_count' => $this->payment_count,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type
            ],
            'customer' => $this->customer->id ? [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
            ] : null,
            'payments' => $this->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'account' => $payment->account ? [
                        'id' => $payment->account?->id,
                        'name' => $payment->account?->name,
                    ] : null,
                    'payment_method' => $payment->paymentMethod ? [
                        'id' => $payment->paymentMethod?->id,
                        'name' => $payment->paymentMethod?->name,
                    ] : null,
                    'amount' => $payment->amount,
                    'discount' => $payment->discount,
                    'increase' => $payment->increase,
                    'created_at' => DatetHelper::toBR($payment->created_at),
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
