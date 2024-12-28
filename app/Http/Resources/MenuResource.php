<?php

namespace App\Http\Resources;

use App\Helpers\DatetHelper;
use App\Helpers\StatusHelper;
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
                'name' => StatusHelper::getStatusName($this->status),
            ],
            'children' => MenuResource::collection($this->whenLoaded('children')),
            'updated_at' => DatetHelper::toBR($this->updated_at),
            'created_at' => DatetHelper::toBR($this->created_at)
        ];

        return $menuArray;
    }
}
