<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use ArrayObject;
use Exception;

/**
 * @ignore
 * @extends ArrayObject<string, string>
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
        if ($this->offsetExists($key)) {
            return parent::offsetGet($key);
        }

        $this->accessViolation('getting non-existing key from');
    }

    public function offsetExists(mixed $key): bool
    {
        return parent::offsetExists($key);
    }

    protected function accessViolation(
        string $accessDescription = 'reading or modifying',
        string $objectType = 'ImmutableArrayObject'
    ): void {
        throw new Exception(sprintf(
            'Access violation %s %s',
            $accessDescription,
            $objectType
        ));
    }
}
