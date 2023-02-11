<?php

namespace App\Helpers;

class Format
{
    /**
     * @param string $sku
     * @return string
     */
    public static function skuFormat(string $sku): string
    {
        return str_replace('/', '-', $sku);
    }

    /**
     * @param string $handle
     * @return string
     */
    public static function handleFormat(string $handle): string
    {
        return strtolower(self::skuFormat($handle));
    }

    /**
     * @param $string
     * @return float
     */
    public static function toFloat($string): float
    {
        return floatval(preg_replace("/[^-0-9\.]/",'',$string));
    }

    /**
     * @param $data
     * @param bool $default
     * @return bool
     */
    public static function toBool($data, bool $default = false): bool
    {
        return match (gettype($data)) {
            'string' => $data == '' ? $default : filter_var($data, FILTER_VALIDATE_BOOLEAN),
            default   => is_null($data) ? $default : filter_var($data, FILTER_VALIDATE_BOOLEAN)
        };
    }
}
