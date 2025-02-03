<?php

namespace App\Helpers;

class PhoneHelper
{
    public static function sanitize(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    public static function isValid(string $phone): bool
    {
        $phone = self::sanitize($phone);

        if (!in_array(strlen($phone), [10, 11])) {
            return false;
        }

        $ddd = substr($phone, 0, 2);
        if (!preg_match('/^[1-9][0-9]$/', $ddd)) {
            return false;
        }

        if (strlen($phone) === 11 && substr($phone, 2, 1) !== '9') {
            return false;
        }

        return true;
    }
}