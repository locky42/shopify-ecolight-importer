<?php

namespace App\Services;

use Exception;
use App\Exceptions\ExceptionToMail;
use App\Models\Option;
use App\Helpers\Api\Image;
use App\Helpers\Api\Variant;
use App\Helpers\Api\Collection;
use App\Helpers\Error;
use App\Models\Session;
use App\Models\Products;
use App\Models\ShopifyProduct;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;
use Shopify\Exception\RestResourceException;
use Shopify\Exception\RestResourceRequestException;
use Shopify\Rest\Admin2023_01\Product;
use Shopify\Auth\Session as AuthSession;
use App\Helpers\Api\Session as SessionHelper;
use Illuminate\Support\Facades\Log;

class EcolightUpdateService
{
    protected ApiProducts $apiProductsService;

    public function __construct()
    {
        Log::info('Import START');
        $this->apiProductsService = new ApiProducts();
    }

    /**
     * @return array
     */
    public function getProducts(): array
    {
        /**
         * @var $apiProduct ShopifyProduct
         * @var $option Option
         */

        $apiProducts = $this->apiProductsService->getProducts();
        $products = [];

        foreach ($apiProducts as $apiProduct) {
            $variantsOptions = [];
            $options = [];

            foreach ($apiProduct->options as $iteration => $option) {
                if ($option->value) {
                    $variantsOptions["option" . $iteration + 1] = $option->value;
                    $options[] = ['name' => $option->name];
                }
            }

            $variants = [
                array_merge([
                    "sku"     => $apiProduct->variantSku,
                    "price"   => $apiProduct->variantPrice,
                    "grams"   => $apiProduct->variantGrams,
                    "taxable" => $apiProduct->variantTaxable,
                ], $variantsOptions),
            ];

            $product = [
                'title' => $apiProduct->title,
                'body_html' => $apiProduct->bodyHtml,
                'seo_title' => $apiProduct->seoTitle,
                'seo_description' => $apiProduct->seoDescription,
                'handle' => $apiProduct->handle,
                'vendor' => $apiProduct->vendor,
                'product_type' => $apiProduct->customProductType,
                'published' => $apiProduct->status == $apiProduct::STATUS_ACTIVE,
                'variants' => $variants,
                'options' => $options,
                'collection' => $apiProduct->collection,
                'images' => [
                    [
                        'src' => $apiProduct->imageSrc,
                        'alt' => $apiProduct->imageAltText
                    ]
                ],
            ];

            $products[] = $product;
        }

        return $products;
    }

    /**
     * @return void
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     * @throws RestResourceRequestException
     */
    public function import(): void
    {
        $sessionModel = new Session();
        $sessions = $sessionModel::all();

        foreach ($sessions as $session) {
            $authSession = new AuthSession($session->id, $session->shop, $session->is_online ?? $session->isOnline, $session->state);
            $authSession->setAccessToken($session->access_token);
            SessionHelper::setSession($authSession);
            foreach ($this->getProducts() as $product) {
                try {
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
                                Log::info('Product ' . $sku . ' (id:' . $localProductArray['product_id'] . ') already exist');
                            } else {
                                $productId = $localProductArray['product_id'];
                                Log::info('Update ' . $sku . ' product (id:' . $productId . ')');
                                $result = $this->sendProduct($product, $productId);
                                if (isset($result->errors)) {
                                    Log::debug(var_export($result, true));
                                    throw new Exception(var_export($result, true));
                                }
                                Products::addWriteLocalProduct($sku, $productId, $productHash, $localProduct);
                            }
                        } else {
                            Log::info('Insert ' . $sku);
                            $result = $this->sendProduct($product);

                            $productId = $result->id;
                            Log::info("Product $sku has id $productId");
                            if ($productId) {
                                Products::addWriteLocalProduct($sku, $productId, $productHash);
                            }
                        }
                    }
                } catch (ExceptionToMail $exception) {
                    Log::error($exception->getMessage());
                    Error::addError($exception->getMessage());
                } catch (Exception $exception) {
                    Error::addError($exception->getMessage());
                }
            }
            break;
        }
    }

    /**
     * @param $product
     * @param $productId
     * @return Product
     * @throws ExceptionToMail
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     * @throws RestResourceRequestException
     */
    protected function sendProduct($product, $productId = null): Product
    {
        $shopifyProduct = new Product(SessionHelper::get());
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
                $this->sendProduct($product);
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
            Product::delete(SessionHelper::get(), $shopifyProduct->id);
            throw $exception;
        }

        return $shopifyProduct;
    }

    public function __destruct()
    {
        Log::info('Import END');
        print_r(Error::getErrors());
    }
}
