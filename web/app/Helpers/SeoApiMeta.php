<?php

namespace App\Helpers;

class SeoApiMeta
{
    /**
     * @param $product
     * @return string
     */
    public static function generateTitle($product): string
    {
        return 'Shop Diamond ' . $product->Stone_NO . ' | Luxury Diamonds';
    }

    /**
     * @param $product
     * @return string
     */
    public static function generateDescription($product): string
    {
        return 'Buy Diamond ' . $product->Stone_NO . ' Online in Vancouver Canada.';
    }
}
