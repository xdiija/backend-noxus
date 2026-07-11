<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdoptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adoption_date' => DateHelper::toBR($this->adoption_date),
            'agreement_signed' => $this->agreement_signed,
            'return_date' => DateHelper::toBR($this->return_date),
            'return_reason' => $this->return_reason,
            'notes' => $this->notes,
            'animal' => new AnimalResource($this->whenLoaded('animal')),
            'adopter' => new AdopterResource($this->whenLoaded('adopter')),
            'status' => new AdoptionStatusResource($this->whenLoaded('status')),
            'follow_ups' => AdoptionFollowUpResource::collection($this->whenLoaded('followUps')),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
