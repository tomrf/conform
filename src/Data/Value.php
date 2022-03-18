<?php

declare(strict_types=1);

namespace Tomrf\Conform\Data;

use Countable;
use RuntimeException;
use Serializable;
use Stringable;

class Value implements Stringable, Countable, Serializable
{
    public function __construct(
        protected string $data = ''
    ) {
    }

    public function __toString(): string
    {
        return $this->data;
    }

    public function serialize(): string
    {
        return $this->asBase64();
    }

    public function unserialize(string $data): void
    {
        $string = base64_decode($data, true);
        if (\is_bool($string)) {
            throw new RuntimeException(
                'Failed to unserialize Value data, base64_decode() returned false'
            );
        }
        $this->data = $data;
    }

    public function count(): int
    {
        return mb_strlen($this->data);
    }

    public function asString(): string
    {
        return $this->data;
    }

    public function asInteger(): int
    {
        return (int) $this->data;
    }

    public function asFloat(): float
    {
        return (float) $this->data;
    }

    public function asBase64(): string
    {
        return base64_encode($this->data);
    }

    public function asMd5Hex(): string
    {
        return md5($this->data);
    }

    public function asBoolean(): bool
    {
        return (bool) ($this->data);
    }

    public function isNumeric(): bool
    {
        return is_numeric($this->data);
    }

    public function isNull(): bool
    {
        return false;
    }
}
