<?php

namespace App\Http\Resources;

use App\Enums\Status;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'status' => [
                'id' => $this->status,
                'name' => Status::tryFrom($this->status)?->label(),
            ],
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at)
        ];
    }
}
