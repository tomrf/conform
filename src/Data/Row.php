<?php

declare(strict_types=1);

namespace Tomrf\Conform\Data;

class Row extends ImmutableArrayObject
{
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
