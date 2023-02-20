<?php

namespace App\Services;

use Exception;
use App\Exceptions\ExceptionToMail;
use App\Helpers\Api\Product as ApiShopifyProduct;
use App\Helpers\Error;
use App\Helpers\Api\Session as SessionHelper;
use App\Models\Option;
use App\Models\Session;
use App\Models\Products;
use App\Models\ShopifyProduct;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;
use Shopify\Exception\RestResourceException;
use Shopify\Exception\RestResourceRequestException;
use Shopify\Auth\Session as AuthSession;
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
            $countExist = 0;
            $authSession = new AuthSession($session->id, $session->shop, $session->is_online ?? $session->isOnline, $session->state);
            $authSession->setAccessToken($session->access_token);
            SessionHelper::setSession($authSession);
            $products = $this->getProducts();
            foreach ($products as $product) {
                $productLocalSku = null;
                $productLocalId = 0;
                $productLocalHash = null;
                try {
                    if (is_object($product)) {
                        $product = $product->toArray();
                    }

                    $productHash = md5(serialize($product));
                    $productLocalHash = $productHash;
                    foreach ($product['variants'] as $variant) {
                        $sku = $variant['variantSku'] ?? $variant['sku'];
                        $productLocalSku = $sku;
                        $localProduct = Products::getShopifyProductByHash($productHash);
                        $localProductArray = $localProduct?->toArray();
                        if ($localProductArray) {
                            if ($localProductArray['product_hash'] == $productHash) {
                                $countExist++;
//                                Log::info('Product ' . $sku . ' (id:' . $localProductArray['product_id'] . ') already exist');
                            } else {
                                $productId = $localProductArray['product_id'];
                                $productLocalId = $productId;
                                Log::info('Update ' . $sku . ' product (id:' . $productId . ')');
                                $result = ApiShopifyProduct::sendProduct($product, $productId);
                                if (isset($result->errors)) {
                                    Log::debug(var_export($result, true));
                                    throw new Exception(var_export($result, true));
                                }
                                Products::addWriteLocalProduct($sku, $productId, $productHash, $localProduct);
                            }
                        } else {
                            Log::info('Insert ' . $sku);
                            $result = ApiShopifyProduct::sendProduct($product);

                            $productId = $result->id;
                            $productLocalId = (int) $productId;
                            Log::info("Product $sku has id $productId");
                            if ($productId) {
                                Products::addWriteLocalProduct($sku, $productId, $productHash);
                            }
                        }
                    }
                    $productLocalSku = null;
                } catch (ExceptionToMail $exception) {
                    Log::error($exception->getMessage());
                    Error::addError($exception->getMessage());
                    Products::addWriteLocalProduct($productLocalSku, $productLocalId, $productLocalHash);
                } catch (Exception $exception) {
                    Error::addError($exception->getMessage() . ' | ' . $exception->getFile() . ':' . $exception->getLine());
                }
            }

            if ($countExist) {
                Log::info("Skip $countExist products (already exist)");
            }

            try {
                $this->clearProducts($products);
            } catch (Exception $exception) {
                Error::addError($exception->getMessage());
            }

            break;
        }
    }

    /**
     * @param array $apiProducts
     * @return void
     */
    protected function clearProducts(array $apiProducts): void
    {
        $clearProductsService = new ClearProductsService();
        $clearProductsService
            ->setProductsFromApi($apiProducts)
            ->execute();
    }

    public function __destruct()
    {
        if (!empty(Error::getErrors())) {
            Log::warning(var_export(Error::getErrors(), true));
        }

        Log::info('Import END');
    }
}
