<?php

namespace App\Helpers\Api;

use App\Helpers\ProductConstants;
use Shopify\Exception\RestResourceException;
use Shopify\Rest\Admin2023_01\CustomCollection;
use Shopify\Rest\Admin2023_01\Product;

class Collection
{
    /**
     * @var array
     */
    protected static array $productData = [];

    /**
     * @var Product|null
     */
    protected static ?Product $shopifyProduct = null;

    protected static array $allCollections = [];

    /**
     * @param array $productData
     * @return void
     */
    public static function setProductData(array $productData): void
    {
        self::$productData = $productData;
    }

    /**
     * @param Product $shopifyProduct
     * @return void
     */
    public static function setShopifyProduct(Product $shopifyProduct): void
    {
        self::$shopifyProduct = $shopifyProduct;
    }

    /**
     * @param Product $shopifyProduct
     * @param array $productData
     * @return void
     * @throws RestResourceException
     */
    public static function setProductCollection(Product $shopifyProduct, array $productData): void
    {
        self::setProductData($productData);
        self::setShopifyProduct($shopifyProduct);
        $collections = self::getAllCollections();
        usleep(500000);
        $collection = $collections[strtoupper($productData[ProductConstants::PRODUCT_COLLECTION])] ?? self::createCollection();
        self::addProductToCollection($collection);
    }

    /**
     * @return array
     */
    protected static function getAllCollections(): array
    {
        $allCollections = CustomCollection::all(Session::get());
        usleep(500000);

        $collections = [];

        foreach ($allCollections as $collection) {
            $allCollections[strtoupper($collection->title)] = $collection;
        }

        self::$allCollections = $collections;
        return $collections;
    }

    /**
     * @param CustomCollection $collection
     * @return void
     * @throws RestResourceException
     */
    protected static function addProductToCollection(CustomCollection $collection): void
    {
        self::removeProductFromCollections();
        Collect::addProductToCollect(self::$shopifyProduct->id, $collection->id);
    }

    /**
     * @return void
     */
    protected static function removeProductFromCollections(): void
    {
        $collects = Collect::getCollectionsProducts();
        foreach ($collects as $products) {
            if (in_array(self::$shopifyProduct->id, $products)) {
                Collect::deleteProductFromCollection(array_flip($products)[self::$shopifyProduct->id]);
            }
        }
    }

    /**
     * @return CustomCollection
     * @throws RestResourceException
     */
    protected static function createCollection(): CustomCollection
    {
        $collection = new CustomCollection(Session::get());
        $collection->title = self::$productData[ProductConstants::PRODUCT_COLLECTION];
        $collection->save(true);

        return $collection;
    }
}
