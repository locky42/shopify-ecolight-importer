<?php

namespace App\Models;

use App\Helpers\Format;
use App\Helpers\ProductConstants;

class ShopifyProduct extends AbstractMemoryModel
{
    const DEFAULT_PUBLISHED = true;

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
     * @var string|int|float
     */
    public string|int|float $variantGrams = '0.00';

    /**
     * @var string|int|float
     */
    public string|int|float $variantPrice = '0.00';

    /**
     * @var bool
     */
    public bool $variantTaxable = true;

    /**
     * @var string|null
     */
    public ?string $imageSrc = null;

    /**
     * @var string|null
     */
    public ?string $imageAltText = null;

    /**
     * @var string|null
     */
    public ?string $seoTitle = null;

    /**
     * @var string|null
     */
    public ?string $seoDescription = null;

    /**
     * @var string
     */
    public string $status = ProductConstants::STATUS_ACTIVE;

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
     * @param $customProductType
     * @return $this
     */
    public function setCustomProductType($customProductType): self
    {
        $this->customProductType = $customProductType;
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
     * @param $variantPrice
     * @return $this
     */
    public function setVariantPrice($variantPrice): self
    {
        $this->variantPrice = number_format(Format::toFloat($variantPrice), 2, '.', '');
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
     * @param $imageAltText
     * @return $this
     */
    public function setImageAltText($imageAltText): self
    {
        $this->imageAltText = $imageAltText;
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
     * @param $status
     * @return $this
     */
    public function setStatus($status): self
    {
        if (is_bool($status)) {
            $this->status = $status ? ProductConstants::STATUS_ACTIVE : ProductConstants::STATUS_DRAFT;
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
        $vars[ProductConstants::PRODUCT_OPTIONS] = $this->optionsToArray($vars);
        return $vars;
    }

    protected static function optionsToArray(array $product): array
    {
        $options = [];
        foreach ($product[ProductConstants::PRODUCT_OPTIONS] as $option) {
            $options[$option->name] = $option->value;
        }

        return $options;
    }
}
