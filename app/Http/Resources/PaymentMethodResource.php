<?php

namespace App\Http\Resources;

use App\Helpers\DatetHelper;
use App\Helpers\StatusHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => [
                'id' => $this->status,
                'name' => StatusHelper::getStatusName($this->status),
            ],
            'updated_at' => DatetHelper::toBR($this->updated_at),
            'created_at' => DatetHelper::toBR($this->created_at),
        ];
    }
}
