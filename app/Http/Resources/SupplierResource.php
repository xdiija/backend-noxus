<?php

namespace App\Http\Resources;

use App\Enums\Status;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome_fantasia' => $this->nome_fantasia,
            'razao_social' => $this->razao_social,
            'inscricao_estadual' => $this->inscricao_estadual,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'status' => [
                'id' => $this->status,
                'name' => Status::tryFrom($this->status)?->label(),
            ],
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}