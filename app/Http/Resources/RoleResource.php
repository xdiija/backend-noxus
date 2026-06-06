<?php

namespace App\Http\Resources;

use App\Enums\Status;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includePermissions = $request->query('show_permissions', false);

        $menuArray = [
            'id' => $this->id,
            'name' => $this->name,
            'status' => [
                'id' => $this->status,
                'name' => Status::tryFrom($this->status)?->label(),
            ],
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at)
        ];

        if ($includePermissions) {
            $menuArray['permissions'] = $this->menus->map(function ($menu) {
                return [
                    'menu_id' => $menu->id,
                    'can_view' => (int) $menu->pivot->can_view,
                    'can_create' => (int) $menu->pivot->can_create,
                    'can_update' => (int) $menu->pivot->can_update,
                ];
            });
        }

        return $menuArray;
    }
}