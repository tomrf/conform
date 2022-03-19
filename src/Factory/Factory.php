<?php

declare(strict_types=1);

namespace Tomrf\Conform\Factory;

use Tomrf\Conform\Interface\FactoryInterface;

class Factory implements FactoryInterface
{
    /**
     * @param class-string $class
     */
    public function __construct(
        protected string $class
    ) {
    }

    public function make(mixed ...$params): mixed
    {
        return new $this->class(...$params);
    }
}
