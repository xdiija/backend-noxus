<?php

namespace App\Http\Resources;

use App\Helpers\DatetHelper;
use App\Helpers\StatusHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank' => $this->when($this->bank_id, function () {
                return [
                    'id' => $this->bank?->id,
                    'name' => $this->bank?->name,
                    'agency' => $this->agency,
                    'number' => $this->number,
                    'phone' => $this->phone,
                ];
            }),
            'name' => $this->name,
            'type' => $this->type,
            'is_default' => (bool) $this->is_default,
            'balance' => $this->balance,
            'status' => [
                'id' => $this->status,
                'name' => StatusHelper::getStatusName($this->status),
            ],
            'updated_at' => DatetHelper::toBR($this->updated_at),
            'deleted_at' => $this->when(
                $this->deleted_at,
                fn () => DatetHelper::toBR($this->deleted_at)
            ),
            'created_at' => DatetHelper::toBR($this->created_at)
        ];
    }
}
