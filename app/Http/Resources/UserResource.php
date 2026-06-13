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
            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ] : null,
            'last_login' => DateHelper::toBR($this->last_login),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at)
        ];
    }
}
