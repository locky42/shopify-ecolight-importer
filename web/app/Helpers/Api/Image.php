<?php

namespace App\Helpers\Api;

use Exception;
use Shopify\Exception\RestResourceException;
use Shopify\Rest\Admin2023_01\Image as CoreImage;
use Shopify\Rest\Admin2023_01\Product;

class Image
{
    /**
     * @param Product $shopifyProduct
     * @param array $productData
     * @return void
     * @throws RestResourceException
     */
    public static function setProductImages(Product $shopifyProduct, array $productData): void
    {
        self::removeProductImages($shopifyProduct);

        foreach ($productData['images'] as $iteration => $imageData) {
            $image = new CoreImage(Session::get());
            $image->product_id = $shopifyProduct->id;
            $image->position = $iteration + 1;
            $image->alt = $imageData['alt'] ?? '';
            $image->src = $imageData['src'];
            if ($image->src) {
                try {
                    $image->save(
                        true
                    );
                } catch (Exception $e) {
                    // TODO: add log errors
                }
            }
        }
    }

    /**
     * @param Product $shopifyProduct
     * @return void
     */
    public static function removeProductImages(Product $shopifyProduct): void
    {
        foreach ($shopifyProduct->images as $image) {
            CoreImage::delete(
                Session::get(),
                $image->id,
                ["product_id" => $shopifyProduct->id]
            );
        }
    }
}
