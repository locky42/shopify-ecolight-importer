<?php

namespace App\Helpers;

class SeoMeta
{
    const KEY_MODEL = 'StoneNo';

    /**
     * @param array $fileKeys
     * @param array $originalProduct
     * @param int $seoTitleId
     * @return string
     */
    public static function generateTitle(array $fileKeys, array $originalProduct, int $seoTitleId): string
    {
        return $originalProduct[$seoTitleId] ? : 'Shop Diamond ' . self::getModelName($fileKeys, $originalProduct) . ' | Luxury Diamonds';
    }

    /**
     * @param array $fileKeys
     * @param array $originalProduct
     * @param int $seoDescriptionId
     * @return string
     */
    public static function generateDescription(array $fileKeys, array $originalProduct, int $seoDescriptionId): string
    {
        return $originalProduct[$seoDescriptionId] ? : 'Buy Diamond ' . self::getModelName($fileKeys, $originalProduct) . ' Online in Vancouver Canada.';
    }

    /**
     * @param array $fileKeys
     * @param array $originalProduct
     * @return string
     */
    public static function getModelName(array $fileKeys, array $originalProduct): string
    {
        $idModel= ArrayHelper::getValueId($fileKeys, self::KEY_MODEL);
        return $originalProduct[$idModel];
    }
}
