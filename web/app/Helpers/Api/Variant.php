<?php

namespace App\Helpers\Api;

use Shopify\Clients\Graphql;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;
use Shopify\Rest\Admin2023_01\Variant as CoreVariant;
use App\Helpers\Config;

class Variant
{
    /**
     * @param $sku
     * @return bool|int|null
     * @throws HttpRequestException
     * @throws MissingArgumentException
     */
    public static function getShopifyProductIdByVariantsBySku($sku): bool|int|null
    {
        $client = new Graphql(Session::get()->shop, Config::get('api_key'));

        $query = <<<QUERY
            query {
                productVariants(first: 1, query: "sku:$sku") {
                    edges {
                        node {
                            id
                        }
                    }
                }
            }
        QUERY;

        $response = $client->query(["query" => $query])->getBody()->getContents();

        $productId = false;
        foreach (json_decode($response)?->data?->productVariants?->edges as $variant) {
            $parts = explode('/', $variant->node->id);
            $variantId = end($parts);
            $shopifyVariant = CoreVariant::find(Session::get(), $variantId);
            $productId = $shopifyVariant->product_id;
            break;
        }

        return $productId;
    }
}
