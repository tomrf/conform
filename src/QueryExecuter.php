<?php

declare(strict_types=1);

namespace Tomrf\Snek;

use PDOStatement;

abstract class QueryExecuter
{
    abstract public function findOne(string $query, array $queryParameters): Row|bool;

    /**
     * @param array<string,mixed> $queryParameters
     *
     * @throws Exception
     *
     * @return array<int,mixed>
     */
    abstract public function findMany(string $query, array $queryParameters): array;

    /**
     * @param array<string,mixed> $queryParameters
     *
     * @throws Exception
     */
    abstract protected function executeQuery(string $query, array $queryParameters): PDOStatement;

    /**
     * @return array<int,mixed>
     */
    abstract protected function fetchAllRows(PDOStatement $statement): array;

    abstract protected function fetchRow(PDOStatement $statement): Row|false;
}