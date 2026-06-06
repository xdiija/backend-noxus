<?php

namespace App\DTOs\Supplier;

use App\Http\Requests\Supplier\StoreUpdateSupplierRequest;

class SupplierDTO
{
    public function __construct(
        public readonly string $nome_fantasia,
        public readonly string $razao_social,
        public readonly ?string $inscricao_estadual,
        public readonly ?string $email,
        public readonly string $cnpj,
        public readonly ?string $phone_1,
        public readonly ?string $phone_2,
        public readonly int $status,
    ) {}

    public static function fromRequest(StoreUpdateSupplierRequest $request): self
    {
        $data = $request->validated();

        return new self(
            nome_fantasia: $data['nome_fantasia'],
            razao_social: $data['razao_social'],
            inscricao_estadual: $data['inscricao_estadual'] ?? null,
            email: $data['email'] ?? null,
            cnpj: $data['cnpj'],
            phone_1: $data['phone_1'] ?? null,
            phone_2: $data['phone_2'] ?? null,
            status: (int) $data['status'],
        );
    }

    public function toArray(): array
    {
        return [
            'nome_fantasia' => $this->nome_fantasia,
            'razao_social' => $this->razao_social,
            'inscricao_estadual' => $this->inscricao_estadual,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'status' => $this->status,
        ];
    }
}
