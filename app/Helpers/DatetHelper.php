<?php

namespace App\Helpers;

use Carbon\Carbon;

class DatetHelper
{
    /**
     * Convert a database date or timestamp to BR format (dd/mm/yyyy or dd/mm/yyyy HH:mm:ss).
     */
    public static function toBR(?string $date, bool $includeTime = false): ?string
    {
        if (!$date) return null;
            
        $format = $includeTime ? 'd/m/Y H:i:s' : 'd/m/Y';
        return Carbon::parse($date)->format($format);
    }

    /**
     * Convert a BR date or timestamp to database format (yyyy-mm-dd or yyyy-mm-dd HH:mm:ss).
     */
    public static function toDatabase(?string $date, bool $includeTime = false): ?string
    {
        if (!$date) return null;
            
        $formatTo = $includeTime ? 'Y-m-d H:i:s' : 'Y-m-d';
        $formatFrom = $includeTime ? 'd/m/Y H:i:s' : 'd/m/Y';
        return Carbon::createFromFormat($formatFrom, $date)->format($formatTo);
    }
}