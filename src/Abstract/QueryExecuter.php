<?php

declare(strict_types=1);

namespace Tomrf\Conform\Abstract;

use Tomrf\Conform\Row;

abstract class QueryExecuter
{
    /**
     * @param array<string,mixed> $queryParameters
     */
    abstract public function findOne(string $query, array $queryParameters): Row|bool;

    /**
     * @param array<string,mixed> $queryParameters
     *
     * @throws \Exception
     *
     * @return array<int,mixed>
     */
    abstract public function findMany(string $query, array $queryParameters): array;
}
