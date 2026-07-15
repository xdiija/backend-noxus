<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KennelStayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_date' => DateHelper::toBR($this->entry_date),
            'exit_date' => DateHelper::toBR($this->exit_date),
            'is_open' => $this->exit_date === null,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'animal' => new AnimalResource($this->whenLoaded('animal')),
            'kennel' => new KennelResource($this->whenLoaded('kennel')),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
