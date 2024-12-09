<?php

namespace App\Http\Resources;

use App\Helpers\DatetHelper;
use App\Helpers\StatusHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => [
                'id' => $this->status,
                'name' => StatusHelper::getStatusName($this->status),
            ],
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'last_login' => DatetHelper::toBR($this->last_login),
            'updated_at' => DatetHelper::toBR($this->updated_at),
            'created_at' => DatetHelper::toBR($this->created_at)
        ];
    }
}
