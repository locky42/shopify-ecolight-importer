<?php

namespace App\Services;

use App\Models\Option;

class OptionsCollection
{
    /**
     * @var array
     */
    protected array $collection = [];

    /**
     * @param $product
     * @param string $key
     * @return $this
     */
    public function add($product, string $key): self
    {
        $option = new Option;
        $option
            ->setName($key)
            ->setValue($product->{$key});
        $this->collection[] = $option;
        return $this;
    }

    /**
     * @return array
     */
    public function getCollection(): array
    {
        return $this->collection;
    }
}
