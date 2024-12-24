<?php

namespace App\Helpers;

class StatusHelper
{
    public const ACTIVE = 1;
    public const INACTIVE = 2;
    public const PENDING = 3;
    public const BLOCKED = 4;
    private static array $statusNames = [
        1 => "Ativo",
        2 => "Inativo",
        3 => "Pendente",
        4 => "Bloqueado"
    ];

    public static function getStatusName(?int $status): ?string
    {
        return self::$statusNames[$status] ?? null;
    }
}