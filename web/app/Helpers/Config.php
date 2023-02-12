<?php

namespace App\Helpers;

class Config
{
    static protected array $config = [];

    /**
     * @param array $config
     * @return void
     */
    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        return self::$config[$key];
    }

    /**
     * @return array
     */
    public static function getAll(): array
    {
        return self::$config;
    }
}
