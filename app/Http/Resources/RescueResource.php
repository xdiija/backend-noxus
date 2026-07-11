<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RescueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rescue_date' => DateHelper::toBR($this->rescue_date, true),
            'location' => $this->location,
            'rescued_by' => $this->rescued_by,
            'condition' => $this->condition,
            'description' => $this->description,
        ];
    }
}
