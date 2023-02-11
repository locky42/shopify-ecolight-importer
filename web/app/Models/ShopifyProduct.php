<?php

namespace App\Models;

use App\Helpers\ArrayHelper;
use App\Helpers\Format;

class ShopifyProduct extends AbstractMemoryModel
{
    const DEFAULT_PUBLISHED = true;
    const STATUS_ACTIVE = 'active';
    const STATUS_DRAFT = 'draft';

    /**
     * @var string|null
     */
    public ?string $handle = null;

    /**
     * @var string|null
     */
    public ?string $title = null;

    /**
     * @var string|null
     */
    public ?string $bodyHtml = null;

    /**
     * @var string|null
     */
    public ?string $vendor = null;

    /**
     * @var string|null
     */
    public ?string $standardizedProductType = null;

    /**
     * @var string|null
     */
    public ?string $customProductType = null;

    /**
     * @var array
     */
    public array $tags = [];

    /**
     * @var bool
     */
    public bool $published = self::DEFAULT_PUBLISHED;

    /**
     * @var array
     */
    public array $options = [];

    /**
     * @var string
     */
    public string $variantSku = '';

    /**
     * @var string
     */
    public $variantGrams = '0.00';

    /**
     * @var string
     */
    public string $variantInventoryTracker = 'shopify';

    /**
     * @var int
     */
    public int $variantInventoryQty = 1;

    /**
     * @var string
     */
    public string $variantInventoryPolicy = 'continue';

    /**
     * @var string
     */
    public string $variantFulfillmentService = 'manual';

    /**
     * @var string
     */
    public $variantPrice = '0.00';

    /**
     * @var string|null
     */
    public ?string $variantCompareAtPrice = null;

    /**
     * @var bool
     */
    public bool $variantRequiresShipping = true;

    /**
     * @var bool
     */
    public bool $variantTaxable = true;

    /**
     * @var string|null
     */
    public ?string $variantBarcode = null;

    /**
     * @var string|null
     */
    public ?string $imageSrc = null;

    /**
     * @var int
     */
    public int $imagePosition = 1;

    /**
     * @var string|null
     */
    public ?string $imageAltText = null;

    /**
     * @var bool
     */
    public bool $giftCard = false;

    /**
     * @var string|null
     */
    public ?string $seoTitle = null;

    /**
     * @var string|null
     */
    public ?string $seoDescription = null;

    /**
     * @var null
     */
    public $googleShopping_GoogleProductCategory = null;

    /**
     * @var null
     */
    public $googleShopping_Gender = null;

    /**
     * @var null
     */
    public $googleShopping_AgeGroup = null;

    /**
     * @var null
     */
    public $googleShopping_MPN = null;

    /**
     * @var null
     */
    public $googleShopping_AdWordsGrouping = null;

    /**
     * @var null
     */
    public $googleShopping_AdWordsLabels = null;

    /**
     * @var null
     */
    public $googleShopping_Condition = null;

    /**
     * @var null
     */
    public $googleShopping_CustomProduct = null;

    /**
     * @var array
     */
    public array $googleShopping_CustomLabels = [];

    /**
     * @var string|null
     */
    public ?string $variantImage = null;

    /**
     * @var string
     */
    public string $variantWeightUnit = 'g';

    /**
     * @var null
     */
    public $variantTaxCode = null;

    /**
     * @var float|null
     */
    public ?float $costPerItem = null;

    /**
     * @var string
     */
    public string $status = self::STATUS_ACTIVE;

    /**
     * @var string|null
     */
    public ?string $collection = null;

    /**
     * @param $handle
     * @return $this
     */
    public function setHandle($handle): self
    {
        $this->handle = $handle;
        return $this;
    }

    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $bodyHtml
     * @return $this
     */
    public function setBodyHtml($bodyHtml): self
    {
        if ($this->bodyHtml) {
            $this->bodyHtml .= $bodyHtml;
        } else {
            $this->bodyHtml = $bodyHtml;
        }
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setArrayJsonHtml(array $data): self
    {
        $html = '<script>';
        $html .= 'product_data = ' . json_encode($data) . ';';
        $html .= '</script>';
        $this->setBodyHtml($html);
        return $this;
    }

    /**
     * @param $vendor
     * @return $this
     */
    public function setVendor($vendor): self
    {
        $this->vendor = $vendor;
        return $this;
    }

    /**
     * @param $standardizedProductType
     * @return $this
     */
    public function setStandardizedProductType($standardizedProductType): self
    {
        $this->standardizedProductType = $standardizedProductType;
        return $this;
    }

    /**
     * @param $customProductType
     * @return $this
     */
    public function setCustomProductType($customProductType): self
    {
        $this->customProductType = $customProductType;
        return $this;
    }

    /**
     * @param $tags
     * @return $this
     */
    public function setTags($tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function setTag(string $tag): self
    {
        $this->tags[] = $tag;
        return $this;
    }

    /**
     * @param $published
     * @return $this
     */
    public function setPublished($published): self
    {
        $this->published = $published;
        return $this;
    }

    /**
     * @param $options
     * @return $this
     */
    public function setOptions($options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param $variantSku
     * @return $this
     */
    public function setVariantSku($variantSku): self
    {
        $this->variantSku = $variantSku;
        return $this;
    }

    /**
     * @param $variantGrams
     * @return $this
     */
    public function setVariantGrams($variantGrams): self
    {
        $this->variantGrams = number_format($variantGrams, 2, '.', '');
        return $this;
    }

    /**
     * @param $variantInventoryTracker
     * @return $this
     */
    public function setVariantInventoryTracker($variantInventoryTracker): self
    {
        $this->variantInventoryTracker = $variantInventoryTracker;
        return $this;
    }

    /**
     * @param $variantInventoryQty
     * @return $this
     */
    public function setVariantInventoryQty($variantInventoryQty): self
    {
        $this->variantInventoryQty = $variantInventoryQty;
        return $this;
    }

    /**
     * @param $variantInventoryPolicy
     * @return $this
     */
    public function setVariantInventoryPolicy($variantInventoryPolicy): self
    {
        $this->variantInventoryPolicy = $variantInventoryPolicy;
        return $this;
    }

    /**
     * @param $variantFulfillmentService
     * @return $this
     */
    public function setVariantFulfillmentService($variantFulfillmentService): self
    {
        $this->variantFulfillmentService = $variantFulfillmentService;
        return $this;
    }

    /**
     * @param $variantPrice
     * @return $this
     */
    public function setVariantPrice($variantPrice): self
    {
        $this->variantPrice = number_format(Format::toFloat($variantPrice), 2, '.', '');
        return $this;
    }

    /**
     * @param $variantCompareAtPrice
     * @return $this
     */
    public function setVariantCompareAtPrice($variantCompareAtPrice): self
    {
        $this->variantCompareAtPrice = $variantCompareAtPrice;
        return $this;
    }

    /**
     * @param $imageSrc
     * @return $this
     */
    public function setImageSrc($imageSrc): self
    {
        $this->imageSrc = $imageSrc;
        return $this;
    }

    /**
     * @param $imagePosition
     * @return $this
     */
    public function setImagePosition($imagePosition): self
    {
        $this->imagePosition = $imagePosition;
        return $this;
    }

    /**
     * @param $imageAltText
     * @return $this
     */
    public function setImageAltText($imageAltText): self
    {
        $this->imageAltText = $imageAltText;
        return $this;
    }

    /**
     * @param $giftCard
     * @return $this
     */
    public function setGiftCard($giftCard): self
    {
        $this->giftCard = $giftCard;
        return $this;
    }

    /**
     * @param $seoTitle
     * @return $this
     */
    public function setSeoTitle($seoTitle): self
    {
        $this->seoTitle = $seoTitle;
        return $this;
    }

    /**
     * @param $seoDescription
     * @return $this
     */
    public function setSeoDescription($seoDescription): self
    {
        $this->seoDescription = $seoDescription;
        return $this;
    }

    /**
     * @param $variantImage
     * @return $this
     */
    public function setVariantImage($variantImage): self
    {
        $this->variantImage = $variantImage;
        return $this;
    }

    /**
     * @param $variantWeightUnit
     * @return $this
     */
    public function setVariantWeightUnit($variantWeightUnit): self
    {
        $this->variantWeightUnit = $variantWeightUnit;
        return $this;
    }

    /**
     * @param $variantTaxCode
     * @return $this
     */
    public function setVariantTaxCode($variantTaxCode): self
    {
        $this->variantTaxCode = $variantTaxCode;
        return $this;
    }

    /**
     * @param $costPerItem
     * @return $this
     */
    public function setCostPerItem($costPerItem): self
    {
        $this->costPerItem = $costPerItem;
        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status): self
    {
        if (is_bool($status)) {
            $this->status = $status ? self::STATUS_ACTIVE : self::STATUS_DRAFT;
        } else {
            $this->status = $status;
        }

        return $this;
    }

    /**
     * @param string|null $collection
     * @return $this
     */
    public function setCollection(?string $collection = null): self
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @param int|null $optionsCount
     * @return array
     */
    public function toArray(?int $optionsCount = null): array
    {
        $vars = parent::toArray();
        $vars['options'] = $this->optionsToArray($vars);
        return $vars;
    }

    protected static function optionsToArray(array $product): array
    {
        $options = [];
        foreach ($product['options'] as $option) {
            $options[$option->name] = $option->value;
        }

        return $options;
    }
}
