<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;

class Error
{
    /**
     * @var array
     */
    protected static array $errors = [];

    /**
     * @param string $error
     * @return void
     */
    public static function addError(string $error): void
    {
        self::$errors[] = $error;
    }

    /**
     * @return array
     */
    public static function getErrors(): array
    {
        return self::$errors;
    }

    /**
     * @param Exception $exception
     * @return void
     */
    public static function exception(Exception $exception): void
    {
        Log::channel('apiImportError')->error(
            $exception->getMessage() .
            PHP_EOL .
            $exception->getFile() .
            ':' . $exception->getLine() .
            PHP_EOL .
            $exception->getTraceAsString()
        );
    }
}
