<?php

namespace App\Http\Resources;

use App\Enums\Status;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $menuArray = [
            'id' => $this->id,
            'name' => $this->name,
            'route' => $this->route,
            'icon' => $this->icon,
            'exclusive_noxus' => boolval($this->exclusive_noxus),
            'parent' => $this->whenLoaded('parent', function () {
                return new MenuResource($this->parent);
            }),
            'order' => $this->order,
            'status' => [
                'id' => $this->status,
                'name' => Status::tryFrom($this->status)?->label(),
            ],
            'children' => MenuResource::collection($this->whenLoaded('children')),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at)
        ];

        return $menuArray;
    }
}
