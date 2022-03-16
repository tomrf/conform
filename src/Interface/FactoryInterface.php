<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

interface FactoryInterface
{
    public function __construct(string $class);

    public function make(mixed ...$params): mixed;
}
