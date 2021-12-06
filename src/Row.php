<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use RuntimeException;

class Row extends ImmutableArrayObject
{
    public function __get(string $name): mixed
    {
        if (!isset($this[$name])) {
            throw new RuntimeException('Access violation reading non-existing property from Row');
        }

        return $this[$name];
    }

    /**
     * @return array <string,mixed>
     */
    public function toArray(): array
    {
        return (array) $this;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
