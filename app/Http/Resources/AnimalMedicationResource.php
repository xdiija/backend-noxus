<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalMedicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medication' => new MedicationResource($this->whenLoaded('medication')),
            'dosage' => $this->dosage,
            'start_date' => DateHelper::toBR($this->start_date),
            // Null = ongoing course.
            'end_date' => DateHelper::toBR($this->end_date),
            'veterinarian' => $this->veterinarian,
            'notes' => $this->notes,
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
