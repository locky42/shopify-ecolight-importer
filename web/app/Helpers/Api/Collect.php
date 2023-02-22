<?php

namespace App\Helpers\Api;

use Shopify\Exception\RestResourceException;
use Shopify\Rest\Admin2023_01\Collect as CoreCollect;

class Collect
{
    /**
     * @var array
     */
    protected static array $collects = [];

    /**
     * @param bool $force
     * @return array
     */
    public static function getAllCollects(bool $force = false): array
    {
        if (empty(self::$collects) || $force) {
            self::$collects = CoreCollect::all(Session::get());
            sleep(1);
        }

        return self::$collects;
    }

    /**
     * @return array
     */
    public static function getCollectionsProducts(): array
    {
        $collectionsProducts = [];
        foreach (self::getAllCollects(true) as $collect) {
            $collectionsProducts[$collect->collection_id][$collect->id] = $collect->product_id;
        }

        return $collectionsProducts;
    }

    /**
     * @param $product_id
     * @param $collection_id
     * @return void
     * @throws RestResourceException
     */
    public static function addProductToCollect($product_id, $collection_id): void
    {
        $collect = new CoreCollect(Session::get());
        $collect->product_id = $product_id;
        $collect->collection_id = $collection_id;

        $collect->save(true);
        sleep(1);
    }

    /**
     * @param $collectId
     * @return void
     */
    public static function deleteProductFromCollection($collectId): void
    {
        CoreCollect::delete(
            Session::get(),
            $collectId
        );
        sleep(1);
    }
}
