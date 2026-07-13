<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FosterHomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'responsible_name' => $this->responsible_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'capacity' => $this->capacity,
            // Open stays (exit_date IS NULL) counted against `capacity`.
            'open_placements_count' => $this->whenCounted('openPlacements'),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
