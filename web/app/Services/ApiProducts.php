<?php

namespace App\Services;

use App\Helpers\Format;
use App\Helpers\SeoApiMeta;
use App\Helpers\ProductChecker;
use App\Helpers\PriceHelper;
use App\Models\ShopifyProduct;
use Illuminate\Support\Facades\Log;

class ApiProducts
{
    const VENDOR = 'EcoLight';
    const PRODUCT_TYPE = 'EcoLight';
    const KEY_OPTION_1 = 'Clarity';
    const KEY_OPTION_2 = 'Color';
    const KEY_OPTION_3 = 'Cut';

    /**
     * @var ApiConnector
     */
    protected ApiConnector $connector;

    public function __construct()
    {
        $this->connector = new ApiConnector();
    }

    /**
     * @param int|null $count
     * @return array
     */
    public function getProducts(?int $count = null): array
    {
        $apiProducts = $this->connector->getProducts();

        Log::info('Get all products from API (' . count($apiProducts) . ')');

        $products = [];
        $iteration = 0;
        foreach ($apiProducts as $apiProduct) {
            if (ProductChecker::check($apiProduct)) {
                if ($count && $iteration >= $count) {
                    break;
                }
                $product = new ShopifyProduct;
                $product
                    ->setHandle(Format::handleFormat($apiProduct->Stone_NO))
                    ->setTitle(
                        implode(' / ', array_filter([
                            trim($apiProduct->Stone_NO),
                            number_format((float) $apiProduct->Weight, 2),
                            trim($apiProduct->Cut),
                            trim($apiProduct->Color),
                            trim($apiProduct->Clarity),
                        ], 'strlen'))
                    )
                    ->setArrayJsonHtml([
                        'product_3d_video' => $apiProduct->Video_url,
                        'product_certificate' => $apiProduct->LabLink ? : $apiProduct->Certificate_file_url
                    ])
                    ->setStatus(ProductChecker::getStatus($apiProduct))
                    ->setSeoTitle(SeoApiMeta::generateTitle($apiProduct))
                    ->setSeoDescription(SeoApiMeta::generateDescription($apiProduct))
                    ->setVariantPrice(PriceHelper::generatePrice($apiProduct))
                    ->setImageSrc($apiProduct->Stone_Img_url)
                    ->setImageAltText($apiProduct->Lab)
                    ->setVariantSku(Format::skuFormat($apiProduct->Stone_NO))
                    ->setVariantGrams((float) $apiProduct->Weight * 100)
                    ->setVendor(self::VENDOR)
                    ->setCollection($apiProduct->Shape)
                    ->setCustomProductType(self::PRODUCT_TYPE)
                    ->setOptions($this->generateOptions($apiProduct));
                $products[] = $product;
                ++$iteration;
            } else {
                Log::info('Skip product ' . $apiProduct->Stone_NO . ' (no valid conditions)');
            }
        }
        return $products;
    }

    /**
     * @param $product
     * @return array
     */
    protected function generateOptions($product): array
    {
        $collection = new OptionsCollection();
        $collection
            ->add($product, self::KEY_OPTION_1)
            ->add($product, self::KEY_OPTION_2)
            ->add($product, self::KEY_OPTION_3);

        return $collection->getCollection();
    }
}
