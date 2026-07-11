<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdoptionFollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follow_up_date' => DateHelper::toBR($this->follow_up_date),
            'contact_method' => $this->contact_method,
            'notes' => $this->notes,
            'status' => new AdoptionFollowUpStatusResource($this->whenLoaded('status')),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
