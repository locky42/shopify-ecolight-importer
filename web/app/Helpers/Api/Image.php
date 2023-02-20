<?php

namespace App\Helpers\Api;

use Exception;
use Shopify\Exception\RestResourceException;
use Shopify\Rest\Admin2023_01\Image as CoreImage;
use Shopify\Rest\Admin2023_01\Product;
use App\Exceptions\ExceptionToMail;
use App\Helpers\ProductConstants;

class Image
{
    /**
     * @param Product $shopifyProduct
     * @param array $productData
     * @return void
     * @throws RestResourceException
     * @throws ExceptionToMail
     */
    public static function setProductImages(Product $shopifyProduct, array $productData): void
    {
        self::removeProductImages($shopifyProduct);

        foreach ($productData[ProductConstants::PRODUCT_IMAGES] as $iteration => $imageData) {
            $image = new CoreImage(Session::get());
            $image->product_id = $shopifyProduct->id;
            $image->position = $iteration + 1;
            $image->alt = $imageData[ProductConstants::PRODUCT_IMAGE_ALT] ?? '';
            $image->src = $imageData[ProductConstants::PRODUCT_IMAGE_SRC];
            try {
                if ($image->src) {
                    $image->save(true);
                }
            } catch (Exception $exception) {
                throw new ExceptionToMail('Import image error (product ' . $productData[ProductConstants::PRODUCT_TITLE] . '): ' . $exception->getMessage());
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
