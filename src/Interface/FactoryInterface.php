<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

interface FactoryInterface
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class);

    public function make(mixed ...$params): mixed;
}
