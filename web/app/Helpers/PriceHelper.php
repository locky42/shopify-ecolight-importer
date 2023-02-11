<?php

namespace App\Helpers;

class PriceHelper
{
    const PRICE_KEY = 'SaleAmt';
    const PRICE_COEFFICIENT = 2.5;
    const DEFAULT_USD_TO_CAD = 1.4;
    const COEFFICIENTS_RULES = [
        'Shape' => [
            'oval' => 3
        ]
    ];

    static $usdToCad;

    const API_URL = 'https://www.bankofcanada.ca/valet//observations/FXUSDCAD/json?recent=1';

    /**
     * @param $product
     * @return float
     */
    public static function generatePrice($product): float
    {
        $price = $product->{self::PRICE_KEY};
        return (float) $price * self::getCoefficient($product) * self::getUsdToCad();
    }

    /**
     * @param $product
     * @return float|int
     */
    public static function getCoefficient($product = null): float|int
    {
        $coefficient = self::PRICE_COEFFICIENT;

        if ($product) {
            foreach (self::COEFFICIENTS_RULES as $key => $coefficientRule)
            {
                $setKey = strtolower($product->{$key});
                if (isset($coefficientRule[$setKey])) {
                    $coefficient = $coefficientRule[$setKey];
                    break;
                }
            }
        }

        return $coefficient;
    }

    /**
     * @return mixed
     */
    public static function getUsdToCad()
    {
        if (!self::$usdToCad) {
            $data = json_decode(file_get_contents(self::API_URL));
            $object = array_shift($data->observations);
            $price = (float) $object->FXUSDCAD->v;
            self::$usdToCad = max($price, self::DEFAULT_USD_TO_CAD);
        }

        return self::$usdToCad;
    }
}
