<?php

namespace App\Helpers\Api;

use App\Exceptions\ExceptionToMail;
use App\Helpers\ProductConstants;
use App\Models\Products;
use Illuminate\Support\Facades\Log;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;
use Shopify\Exception\RestResourceException;
use Shopify\Exception\RestResourceRequestException;
use Shopify\Rest\Admin2023_01\Product as ShopifyProduct;

class Product
{
    /**
     * @param $product
     * @param string $sku
     * @param $productId
     * @return ShopifyProduct|null
     * @throws ExceptionToMail
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     * @throws RestResourceRequestException
     */
    public static function sendProduct($product, string $sku, $productId = null): ?ShopifyProduct
    {
        $shopifyProduct = new ShopifyProduct(Session::get());
        $productId = $productId ? : Variant::getShopifyProductIdByVariantsBySku(
            $product[ProductConstants::PRODUCT_VARIANTS][0][ProductConstants::PRODUCT_VARIANT_SKU]
        );
        if ($productId) {
            $shopifyProduct->id = $productId;
        }

        $shopifyProduct->title = $product[ProductConstants::PRODUCT_TITLE];
        $shopifyProduct->body_html = $product[ProductConstants::PRODUCT_BODY_HTML];
        $shopifyProduct->vendor = $product[ProductConstants::PRODUCT_VENDOR];
        $shopifyProduct->product_type = $product[ProductConstants::PRODUCT_TYPE];
        $shopifyProduct->variants = $product[ProductConstants::PRODUCT_VARIANTS];
        $shopifyProduct->options = $product[ProductConstants::PRODUCT_OPTIONS];
        $shopifyProduct->handle = $product[ProductConstants::PRODUCT_HANDLE];
        $shopifyProduct->metafields_global_title_tag = $product[ProductConstants::PRODUCT_SEO_TITLE];
        $shopifyProduct->metafields_global_description_tag = $product[ProductConstants::PRODUCT_SEO_DESCRIPTION];
        if (!$product[ProductConstants::PRODUCT_PUBLISHED]) {
            $shopifyProduct->status = ProductConstants::STATUS_DRAFT;
        }

        if ($shopifyProduct->id && $shopifyProduct->status != ProductConstants::STATUS_DRAFT) {
            try {
                $shopifyProduct->saveAndUpdate();
                Log::info('Insert ' . $sku);
                usleep(500000);
            } catch (RestResourceRequestException $exception) {
                if ($exception->getMessage() == 'REST request failed: "Not Found"' && $productId) {
                    Products::getShopifyProductById($productId)?->delete();
                    self::sendProduct($product, $sku);
                } else {
                    throw $exception;
                }
            }

            Collection::setProductCollection($shopifyProduct, $product);
            try {
                Image::setProductImages($shopifyProduct, $product);
            } catch (ExceptionToMail $exception) {
                Log::error(implode(' | ', [
                    $exception->getMessage(),
                    $exception->getFile() . ':' . $exception->getLine()
                ]));
                Log::warning("Delete product $sku (id:$shopifyProduct->id)");
                ShopifyProduct::delete(Session::get(), $shopifyProduct->id);
                throw $exception;
            }

            return $shopifyProduct;
        } elseif ($shopifyProduct->id) {
            $localProduct = Products::getShopifyProductById($productId);
            if ($localProduct) {
                $localProduct->setAttribute('product_id', 0);
                $localProduct->save();
                Log::info("Delete product $sku ($productId) from DB");
                ShopifyProduct::delete(Session::get(), $shopifyProduct->id);
            }

            return null;
        } else {
            Log::info("Skip product $sku (status is not active)");

            return null;
        }
    }

    /**
     * @param $productId
     * @return array|null
     */
    public static function removeProduct($productId): ?array
    {
        Products::getShopifyProductById($productId)?->delete();

        return ShopifyProduct::delete(Session::get(), $productId);
    }
}
