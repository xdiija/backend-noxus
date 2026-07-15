<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KennelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'size' => new AnimalSizeResource($this->whenLoaded('size')),
            'status' => new KennelStatusResource($this->whenLoaded('status')),
            'notes' => $this->notes,
            // Open stays (exit_date IS NULL) counted against `capacity` —
            // occupancy is derived, never stored.
            'open_stays_count' => $this->whenCounted('openStays'),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
