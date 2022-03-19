<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

interface QueryExecutorInterface
{
    /**
     * @param array<int|string,mixed> $parameters
     */
    public function execute(
        QueryBuilderInterface|string $query,
        array $parameters = []
    ): static;
}
