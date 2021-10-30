<?php

namespace Tomrf\Snek;

use ArrayObject;
use Exception;

class Row extends ArrayObject
{
    public function offsetSet(mixed $key, mixed $value): void
    {
        throw new Exception('Access violation using offsetSet on immutable Row');
    }

    public function offsetUnset($key)
    {
        throw new Exception('Access violation using offsetUnset on immutable Row');
    }
}
