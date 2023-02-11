<?php

namespace App\Helpers;

class ArrayHelper
{
    /**
     * @param array|null $array
     * @return array
     */
    public static function arrayFlat(?array $array): array
    {
        $data = [];
        if ($array) {
            foreach ($array as $item) {
                if (is_array($item)) {
                    $data = array_merge($data, self::arrayFlat($item));
                } else {
                    $data[] = $item;
                }
            }
        }

        return $data;
    }

    /**
     * @param $array
     * @param $value
     * @return int|string|null
     */
    public static function getValueId($array, $value): int|string|null
    {
        $values = array_keys($array, $value);
        return array_shift($values);
    }
}
