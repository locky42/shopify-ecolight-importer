<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Products;
use Shopify\Clients\Graphql;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;
use Shopify\Exception\RestResourceException;
use Shopify\Rest\Admin2023_01\Product;
use Shopify\Rest\Admin2023_01\Variant;
use  Shopify\Auth\Session as AuthSession;

class EcolightUpdateService
{
    protected array $config = [];
    protected ApiProducts $apiProductsService;

    public function __construct()
    {
        $this->config = config('services.ecolightUpdate');
        $this->apiProductsService = new ApiProducts();
    }

    public function getProducts(): array
    {
//        return $this->apiProductsService->getProducts(2);
        return [
            [
                "title"        => "New Test Product",
                "body_html"    => "<strong>Description!</strong>",
                "vendor"       => "DC",
                "product_type" => "Test",
                "published"    => true ,
                "variants"     => [
                    [
                        "sku"     => "t_009",
                        "option1" => "First_3",
                        "option2" => "Second",
                        "price"   => 30.00,
                        "grams"   => 300,
                        "taxable" => false,
                    ],
                ],
                "options" => [
                    [
                        "name" => "Size"
                    ],
                    [
                        "name" => "Color"
                    ]
                ],
            ]
        ];
    }

    /**
     * @return void
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     */
    public function import(): void
    {
        $sessionModel = new Session();
        $sessions = $sessionModel::all();

        foreach ($sessions as $session) {
            foreach ($this->getProducts() as $product) {
                if (is_object($product)) {
                    $product = $product->toArray();
                }

                $productHash = md5(serialize($product));
                foreach ($product['variants'] as $variant) {
                    $sku = $variant['variantSku'] ?? $variant['sku'];
                    $localProduct = Products::getShopifyProduct($sku);
                    $localProductArray = $localProduct?->toArray();
                    if ($localProductArray) {
                        if ($localProductArray['product_hash'] == $productHash) {
                            echo 'Product ' . $localProductArray['product_id'] . ' already exist' . PHP_EOL;
                        } else {
                            $productId = $localProductArray['product_id'];
                            $result = $this->sendProduct($product, $session, $productId);
                            if (isset($result->errors)) {
                                var_dump($result);
                                die;
                            }
                            Products::addWriteLocalProduct($sku, $productId, $productHash, $localProduct);
                        }
                    } else {
                        $result = $this->sendProduct($product, $session);

                        $productId = $result?->id;
                        if ($productId) {
                            Products::addWriteLocalProduct($sku, $productId, $productHash);
                        }
                    }
                }
            }
            break;
        }
    }

    /**
     * @param $product
     * @param $session
     * @param $productId
     * @return Product
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     */
    protected function sendProduct($product, $session, $productId = null): Product
    {
        $session = new AuthSession($session->id, $session->shop, $session->is_online, $session->state);
        $session->setAccessToken($this->config['api_key']);
        $shopifyProduct = new Product($session);
        if ($productId) {
            $shopifyProduct->id = $productId;
        } else {
            $shopifyProduct->id = $this->getShopifyProductIdByVariantsBySku($product['variants'][0]['sku'] , $session);
        }
        $shopifyProduct->title = $product['title'];
        $shopifyProduct->body_html = $product['body_html'];
        $shopifyProduct->vendor = $product['vendor'];
        $shopifyProduct->product_type = $product['product_type'];
        $shopifyProduct->variants = $product['variants'];
        $shopifyProduct->options = $product['options'];
        if (!$product['published']) {
            $shopifyProduct->status = 'draft';
        }

        $shopifyProduct->saveAndUpdate();
        return $shopifyProduct;
    }

    /**
     * @param $sku
     * @param $session
     * @return false|int|null
     * @throws HttpRequestException
     * @throws MissingArgumentException
     */
    protected function getShopifyProductIdByVariantsBySku($sku, $session)
    {
        $client = new Graphql($session->shop, $this->config['api_key']);

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
            $shopifyVariant = Variant::find($session, $variantId,);
            $productId = $shopifyVariant->product_id;
            break;
        }

        return $productId;
    }
}
