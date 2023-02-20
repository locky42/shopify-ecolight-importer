<?php

namespace App\Services;

use Exception;
use App\Exceptions\ExceptionToMail;
use App\Helpers\Api\Product as ApiShopifyProduct;
use App\Helpers\Error;
use App\Helpers\ProductConstants;
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
    /**
     * @var ApiProducts
     */
    protected ApiProducts $apiProductsService;

    /**
     * @var int
     */
    protected int $countExist = 0;

    /**
     * @var string|null
     */
    protected ?string $productLocalSku = null;

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

        $apiProducts = $this->apiProductsService->getProducts(8);
        $products = [];

        foreach ($apiProducts as $apiProduct) {
            $variantsOptions = [];
            $options = [];

            foreach ($apiProduct->options as $iteration => $option) {
                if ($option->value) {
                    $variantsOptions['option' . $iteration + 1] = $option->value;
                    $options[] = ['name' => $option->name];
                }
            }

            $variants = [
                array_merge([
                    ProductConstants::PRODUCT_VARIANT_SKU => $apiProduct->variantSku,
                    ProductConstants::PRODUCT_VARIANT_PRICE => $apiProduct->variantPrice,
                    ProductConstants::PRODUCT_VARIANT_GRAMS => $apiProduct->variantGrams,
                    ProductConstants::PRODUCT_VARIANT_TAXABLE => $apiProduct->variantTaxable,
                ], $variantsOptions),
            ];

            $product = [
                ProductConstants::PRODUCT_TITLE => $apiProduct->title,
                ProductConstants::PRODUCT_BODY_HTML => $apiProduct->bodyHtml,
                ProductConstants::PRODUCT_SEO_TITLE => $apiProduct->seoTitle,
                ProductConstants::PRODUCT_SEO_DESCRIPTION => $apiProduct->seoDescription,
                ProductConstants::PRODUCT_HANDLE => $apiProduct->handle,
                ProductConstants::PRODUCT_VENDOR => $apiProduct->vendor,
                ProductConstants::PRODUCT_TYPE => $apiProduct->customProductType,
                ProductConstants::PRODUCT_PUBLISHED => $apiProduct->status == ProductConstants::STATUS_ACTIVE,
                ProductConstants::PRODUCT_VARIANTS => $variants,
                ProductConstants::PRODUCT_OPTIONS => $options,
                ProductConstants::PRODUCT_COLLECTION => $apiProduct->collection,
                ProductConstants::PRODUCT_IMAGES => [
                    [
                        ProductConstants::PRODUCT_IMAGE_SRC => $apiProduct->imageSrc,
                        ProductConstants::PRODUCT_IMAGE_ALT => $apiProduct->imageAltText
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
            $products = $this->getProducts();
            foreach ($products as $product) {
                $this->productLocalSku = null;
                $productLocalId = 0;
                $productLocalHash = null;
                try {
                    if (is_object($product)) {
                        $product = $product->toArray();
                    }

                    $productHash = md5(serialize($product));
                    $productLocalHash = $productHash;
                    foreach ($product[ProductConstants::PRODUCT_VARIANTS] as $variant) {
                        $this->saveProduct($product, $variant, $productHash, $productLocalId);
                    }
                    $this->productLocalSku = null;
                } catch (ExceptionToMail $exception) {
                    Log::error($exception->getMessage());
                    Error::addError($exception->getMessage());
                    Products::addWriteLocalProduct($this->productLocalSku, $productLocalId, $productLocalHash);
                } catch (Exception $exception) {
                    Error::addError($exception->getMessage() . ' | ' . $exception->getFile() . ':' . $exception->getLine());
                }
            }

            if ($this->countExist) {
                Log::info("Skip $this->countExist products (already exist)");
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
     * @param $product
     * @param $variant
     * @param $productHash
     * @param $productLocalId
     * @return void
     * @throws ExceptionToMail
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     * @throws RestResourceRequestException
     */
    protected function saveProduct($product, $variant, $productHash, &$productLocalId): void
    {
        $sku = $variant['variantSku'] ?? $variant[ProductConstants::PRODUCT_VARIANT_SKU];
        $this->productLocalSku = $sku;
        $localProduct = Products::getShopifyProductByHash($productHash);
        $localProductArray = $localProduct?->toArray();

        if ($localProductArray) {
            if ($localProductArray['product_hash'] == $productHash) {
                $this->countExist++;
//                Log::info('Product ' . $sku . ' (id:' . $localProductArray['product_id'] . ') already exist');
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

            $productId = $result?->id;
            $productLocalId = (int) $productId;
            Log::info("Product $sku has id $productId");
            if ($productId) {
                Products::addWriteLocalProduct($sku, $productId, $productHash);
            }
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
