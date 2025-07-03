<?php

namespace App\Helpers;

class Money
{
    /**
     * Convert cents (int) to reais (float)
     *
     * @param int $cents
     * @return float
     */
    public static function fromIntToFloat(int $cents): float
    {
        return $cents / 100;
    }

    /**
     * Convert reais (float or string) to cents (int)
     *
     * @param float|string $reais
     * @return int
     */
    public static function fromFloatToInt(float|string $reais): int
    {
        return (int) round(floatval($reais) * 100);
    }

    /**
     * Format cents (int) into BRL currency string (e.g. R$ 50,00)
     *
     * @param int $cents
     * @return string
     */
    public static function formatToBRL(int $cents): string
    {
        $reais = self::fromIntToFloat($cents);
        return 'R$ ' . number_format($reais, 2, ',', '.');
    }
}
