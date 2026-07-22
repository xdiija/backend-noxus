<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_status' => $this->oldStatus ? [
                'id' => $this->oldStatus->id,
                'name' => $this->oldStatus->name,
                'color' => $this->oldStatus->color,
            ] : null,
            'new_status' => [
                'id' => $this->newStatus->id,
                'name' => $this->newStatus->name,
                'color' => $this->newStatus->color,
            ],
            'changed_by' => $this->changedBy ? [
                'id' => $this->changedBy->id,
                'name' => $this->changedBy->name,
            ] : null,
            'notes' => $this->notes,
            'created_at' => DateHelper::toBR($this->created_at, true),
        ];
    }
}
