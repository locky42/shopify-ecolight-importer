<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    /**
     * @param string $sku
     * @return mixed
     */
    public static function getShopifyProduct(string $sku)
    {
        return self::where('product_sku', '=', $sku)->first();
    }

    /**
     * @param $sku
     * @param $productId
     * @param $hash
     * @param $localProduct
     * @return bool
     */
    public static function addWriteLocalProduct($sku, $productId, $hash, $localProduct = null): bool
    {
        $product = $localProduct ?? new self();
        $product->product_id = $productId;
        $product->product_sku = $sku;
        $product->product_hash = $hash;
        return $product->save();
    }
}
