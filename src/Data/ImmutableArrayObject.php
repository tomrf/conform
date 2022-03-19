<?php

declare(strict_types=1);

namespace Tomrf\Conform\Data;

use ArrayObject;
use OutOfBoundsException;

/**
 * @extends ArrayObject<string, mixed>
 */
class ImmutableArrayObject extends ArrayObject
{
    public function __get(string $name): mixed
    {
        if (!isset($this[$name])) {
            $this->accessViolation('reading non-existing key from');
        }

        return $this[$name];
    }

    public function __isset(mixed $name)
    {
        return $this->offsetExists($name);
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->accessViolation('modifying');
    }

    public function offsetUnset(mixed $key): void
    {
        $this->accessViolation('modifying');
    }

    public function offsetGet(mixed $key): mixed
    {
        if ($this->offsetExists((string) $key)) {
            return parent::offsetGet((string) $key);
        }

        $this->accessViolation('getting non-existing key from');

        return null;
    }

    public function offsetExists(mixed $key): bool
    {
        return parent::offsetExists((string) $key);
    }

    protected function accessViolation(
        string $accessDescription = 'reading or modifying',
        string $objectType = 'ImmutableArrayObject'
    ): void {
        throw new OutOfBoundsException(sprintf(
            'Access violation %s %s',
            $accessDescription,
            $objectType
        ));
    }
}
