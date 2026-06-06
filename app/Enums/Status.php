<?php

declare(strict_types=1);

namespace App\Enums;

enum Status: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case PENDING = 3;
    case BLOCKED = 4;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
            self::PENDING => 'Pendente',
            self::BLOCKED => 'Bloqueado',
        };
    }
}
