<?php

namespace Tomrf\Snek;

use ArrayObject;
use Exception;

class Row extends ArrayObject
{
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->accessViolation();
    }

    public function offsetUnset(mixed $key): void
    {
        $this->accessViolation();
    }

    private function accessViolation(): void
    {
        throw new Exception('Access violation modifying immutable Row');
    }
}
