<?php

namespace App\Services;

use App\Models\Products;
use App\Helpers\Api\Product as ApiShopifyProduct;
use Illuminate\Support\Facades\Log;

class ClearProductsService
{
    /**
     * @var array
     */
    protected array $localProducts = [];

    /**
     * @var array
     */
    protected array $apiProducts = [];

    public function __construct()
    {
        $this->localProducts = [];
        $localProducts = Products::all()->toArray();

        foreach ($localProducts as $localProduct) {
            if ($localProduct['product_id']) {
                $this->localProducts[$localProduct['product_sku']] = $localProduct['product_id'];
            }
        }
    }

    /**
     * @param array $products
     * @return $this
     */
    public function setProductsFromApi(array $products): self
    {
        foreach ($products as $apiProduct) {
            foreach ($apiProduct['variants'] as $variant) {
                $this->apiProducts[$variant['sku']] = true;
            }
        }

        return $this;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $products = array_diff_key($this->localProducts, $this->apiProducts);
        foreach ($products as $productId) {
            ApiShopifyProduct::removeProduct($productId);
        }
        Log::info('Remove ' . count($products) . ' products (not exist in vendor api)');
    }
}
