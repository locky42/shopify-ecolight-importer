<?php

namespace App\Helpers;

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
}
