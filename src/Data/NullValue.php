<?php

declare(strict_types=1);

namespace Tomrf\Conform\Data;

class NullValue
{
    public function isNull(): bool
    {
        return true;
    }
}
