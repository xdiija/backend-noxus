<?php

declare(strict_types=1);

namespace App\Helpers;

class CnpjHelper
{
    public static function isValid(string $cnpj): bool
    {
        $cnpj = self::sanitize($cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        for ($t = 12; $t < 14; ++$t) {
            $d = 0;
            $w = $t - 7;
            for ($c = 0; $c < $t; ++$c) {
                $d += $cnpj[$c] * $w;
                $w = ($w == 2) ? 9 : $w - 1;
            }
            $d = $d % 11;
            $d = $d < 2 ? 0 : 11 - $d;
            if ($cnpj[$t] != $d) {
                return false;
            }
        }
        return true;
    }

    public static function sanitize(?string $cnpj): string
    {
        return preg_replace('/[^0-9]/is', '', $cnpj ?? '');
    }

    public static function mask(string $cnpj): string
    {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }

    public static function hide(string $cnpj): string
    {
        return substr($cnpj, 0, 2) . '.***.***/****-' . substr($cnpj, -2);
    }
}
