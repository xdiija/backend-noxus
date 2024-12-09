<?php

namespace App\Helpers;

class StatusHelper
{
    private static array $statusNames = [
        0 => "Inativo",
        1 => "Ativo",
        2 => "Pendente"
    ];

    public static function getStatusName(?int $status): ?string
    {
        return self::$statusNames[$status] ?? null;
    }
}