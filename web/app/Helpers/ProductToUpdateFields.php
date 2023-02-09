<?php

namespace App\Helpers;

class ProductToUpdateFields
{
    /**
     * @param $product
     * @return array|string
     */
    public static function getQueryPart($product): array|string
    {
        return str_replace(
            [
                '"title":',
                '"body_html":',
                '"vendor":',
                '"product_type":',
                '"published":',
                '"variants":',
                '"sku":',
                '"price":',
                '"grams":',
                '"taxable":',
            ],
            [
                'title:',
                'bodyHtml:',
                'vendor:',
                'customProductType:',
                'published:',
                'variants:',
                'sku:',
                'price:',
                'weight:',
                'taxable:',
            ],
            trim(json_encode($product), '{}'));
    }
}
