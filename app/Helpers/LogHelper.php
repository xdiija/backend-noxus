<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class LogHelper
{
    public static function logInfo(string $message, array $data = []): void
    {
        $level = "INFO";
        LogHelper::logJson($level, $message, $data);
    }

    public static function logWarning(string $message, array $data = []): void
    {
        $level = "WARNING";
        LogHelper::logJson($level, $message, $data);
    }

    public static function logThrowable(string $message, \Throwable $th): void
    {
        $level = "TH ERROR";
        $data = [
            'error' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine()
        ];
        LogHelper::logJson($level, $message, $data);
    }

    public static function logError(string $message, array $data): void
    {
        $level = "ERROR";
        LogHelper::logJson($level, $message, $data);
    }

    public static function logJson(string $level, string $message, array $data): void
    {
        $init = '>>>>>>>>>>>>>>>>>>>>';
        $end = '<<<<<<<<<<<<<<<<<<<<';
        $output = $level . PHP_EOL . $init . PHP_EOL . $message . PHP_EOL . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL . $end;
        error_log($output);
    }

}
