<?php

namespace App\Helpers;

class ProductChecker
{
    const ALLOWED_SHAPE = [
        'ROUND',
        'PRINCESS',
        'PEAR',
        'MARQUISE',
        'CUSHION',
        'HEART',
        'OVAL',
        'EMERALD',
        'RADIANT',
        'ASSCHER',
    ];

    /**
     * @param $product
     * @return true
     */
    public static function check($product): bool
    {
        return
            ($product->LabLink || $product->Certificate_file_url) &&
            strtolower($product->Location) == 'ny' &&
            in_array(strtoupper($product->Shape), self::ALLOWED_SHAPE) &&
            (float) $product->Weight >= 0.8;
    }

    /**
     * @param $product
     * @return string
     */
    public static function getStatus($product): string
    {
        return strtolower($product->StockStatus) == 'available' ? ProductConstants::STATUS_ACTIVE : ProductConstants::STATUS_DRAFT;
    }
}
