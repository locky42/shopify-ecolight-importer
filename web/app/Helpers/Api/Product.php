<?php

namespace App\Helpers\Api;

use App\Exceptions\ExceptionToMail;
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
     * @param $productId
     * @return ShopifyProduct
     * @throws ExceptionToMail
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     * @throws RestResourceRequestException
     */
    public static function sendProduct($product, $productId = null): ShopifyProduct
    {
        $shopifyProduct = new ShopifyProduct(Session::get());
        $productId = $productId ? : Variant::getShopifyProductIdByVariantsBySku($product['variants'][0]['sku']);
        if ($productId) {
            $shopifyProduct->id = $productId;
        }

        $shopifyProduct->title = $product['title'];
        $shopifyProduct->body_html = $product['body_html'];
        $shopifyProduct->vendor = $product['vendor'];
        $shopifyProduct->product_type = $product['product_type'];
        $shopifyProduct->variants = $product['variants'];
        $shopifyProduct->options = $product['options'];
        $shopifyProduct->handle = $product['handle'];
        $shopifyProduct->metafields_global_title_tag = $product['seo_title'];
        $shopifyProduct->metafields_global_description_tag = $product['seo_description'];
        if (!$product['published']) {
            $shopifyProduct->status = 'draft';
        }

        try {
            $shopifyProduct->saveAndUpdate();
        } catch (RestResourceRequestException $exception) {
            if ($exception->getMessage() == 'REST request failed: "Not Found"' && $productId) {
                Products::getShopifyProductById($productId)?->delete();
                self::sendProduct($product);
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
            Log::warning('Delete product ' . $shopifyProduct->title . ' (id:' . $shopifyProduct->id . ')');
            ShopifyProduct::delete(Session::get(), $shopifyProduct->id);
            throw $exception;
        }

        return $shopifyProduct;
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
