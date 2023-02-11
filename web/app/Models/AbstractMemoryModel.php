<?php

namespace App\Models;

abstract class AbstractMemoryModel
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
