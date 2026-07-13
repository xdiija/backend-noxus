<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FosterPlacementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_date' => DateHelper::toBR($this->entry_date),
            'exit_date' => DateHelper::toBR($this->exit_date),
            'is_open' => $this->exit_date === null,
            'animal' => new AnimalResource($this->whenLoaded('animal')),
            'foster_home' => new FosterHomeResource($this->whenLoaded('fosterHome')),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
