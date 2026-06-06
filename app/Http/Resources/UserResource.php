<?php

namespace App\Http\Resources;

use App\Enums\Status;
use App\Helpers\DateHelper;
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
                'name' => Status::tryFrom($this->status)?->label(),
            ],
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'last_login' => DateHelper::toBR($this->last_login),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at)
        ];
    }
}
