<?php

namespace Tomrf\Snek;

use RuntimeException;

class Row extends ImmutableArrayObject
{
    public function __get($name)
    {
        if (!isset($this[$name])) {
            throw new RuntimeException('Access violation reading non-existing property from Row');
        }

        return $this[$name];
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
