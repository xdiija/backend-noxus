<?php

namespace App\Http\Resources;

use App\Helpers\DatetHelper;
use App\Helpers\StatusHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => [
                'id' => $this->status,
                'name' => StatusHelper::getStatusName($this->status),
            ],
            'updated_at' => DatetHelper::toBR($this->updated_at),
            'created_at' => DatetHelper::toBR($this->created_at),
            'permissions' => $this->menus->map(function ($menu) {
                return [
                    'menu_id' => $menu->id,
                    'can_view' => (int) $menu->pivot->can_view,
                    'can_create' => (int) $menu->pivot->can_create,
                    'can_update' => (int) $menu->pivot->can_update,
                ];
            }),
        ];
    }
}
