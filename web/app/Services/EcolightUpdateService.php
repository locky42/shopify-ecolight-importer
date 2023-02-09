<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Products;
use Shopify\Clients\Graphql;
use App\Helpers\ProductToUpdateFields;

class EcolightUpdateService
{

    public function getProducts(): array
    {
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
                        "price"   => 30.00,
                        "grams"   => 300,
                        "taxable" => false,
                    ]
                ]
            ]
        ];
    }

    public function getShopifyProduct($sku)
    {
        return Products::where('product_sku', '=', $sku)->first();
    }

    public function import()
    {
        $sessionModel = new Session();
        $sessions = $sessionModel::all();

        foreach ($sessions as $session) {
            $sessionArray = $session->toArray();
            foreach ($this->getProducts() as $product) {
                $productHash = md5(serialize($product));
                foreach ($product['variants'] as $variant) {
                    $sku = $variant['sku'];
                    $localProduct = $this->getShopifyProduct($sku);
                    $localProductArray = $localProduct?->toArray();
                    if ($localProductArray) {
                        if ($localProductArray['product_hash'] == $productHash) {
                            echo 'Product ' . $localProductArray['product_id'] . ' already exist' . PHP_EOL;
                        } else {
                            $productId = $localProductArray['product_id'];
                            $result = $this->sendUpdateProduct($product, $productId, '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',  $sessionArray['shop']);
                            $this->addWriteLocalProduct($sku, $productId, $productHash, $localProduct);
                        }
                    } else {
                        $result = $this->sendProduct(['product' => $product], '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',  $sessionArray['shop']);
                        $productId = $result?->product?->id;
                        if ($result?->product?->id) {
                            $this->addWriteLocalProduct($sku, $productId, $productHash);
                        }
                    }
                }
            }

            break;
        }
    }

    /**
     * @param $sku
     * @param $productId
     * @param $hash
     * @param $localProduct
     * @return bool
     */
    protected function addWriteLocalProduct($sku, $productId, $hash, $localProduct = null): bool
    {
        $product = $localProduct ?? new Products();
        $product->product_id = $productId;
        $product->product_sku = $sku;
        $product->product_hash = $hash;
        return $product->save();
    }

    protected function sendProduct($products, $API_KEY, $SHOP_URL)
    {
        $SHOPIFY_API = "https://$SHOP_URL/admin/api/2023-01/products.json";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $SHOPIFY_API);
        $headers = array(
            'Content-Type: application/json',
            "X-Shopify-Access-Token: $API_KEY"
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($products));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec ($curl);
        curl_close ($curl);

        return json_decode($response);
    }

    protected function sendUpdateProduct($product, $productId, $API_KEY, $SHOP_URL)
    {

        $client = new Graphql($SHOP_URL, $API_KEY);

        $productPartQuery = ProductToUpdateFields::getQueryPart($product);

        $query = <<<QUERY
          mutation {
            productUpdate(input: {id: "gid://shopify/Product/$productId", $productPartQuery}) {
              product {
                id
              }
            }
          }
        QUERY;
        $response = $client->query(["query" => $query])->getBody()->getContents();

        return json_decode($response);
    }
}
