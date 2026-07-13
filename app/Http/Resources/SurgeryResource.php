<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurgeryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'surgery_date' => DateHelper::toBR($this->surgery_date),
            'procedure' => $this->procedure,
            'veterinarian' => $this->veterinarian,
            'clinic' => $this->clinic,
            'cost' => $this->cost,
            'notes' => $this->notes,
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
