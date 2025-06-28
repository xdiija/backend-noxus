<?php

declare(strict_types=1);

namespace App\Helpers;

class CpfHelper
{
    public static function isValid(string $cpf): bool
    {
        $cpf = self::sanitize($cpf);

        if (strlen($cpf) != 11) {
            return false;
        }
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; ++$t) {
            for ($d = 0, $c = 0; $c < $t; ++$c) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    public static function sanitize(?string $cpf): string
    {
        return preg_replace('/[^0-9]/is', '', $cpf ?? '');
    }

    public static function mask(string $cpf): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    public static function hide(string $cpf): string
    {
        return substr($cpf, 0, 3) . '.*.*-' . substr($cpf, -2);
    }
}