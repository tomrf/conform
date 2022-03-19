<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use Tomrf\Conform\Factory\Factory;
use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Pdo\PdoConnection;
use Tomrf\Conform\Pdo\PdoQueryExecutor;

class Conform
{
    public function __construct(
        protected PdoConnection $connection,
        protected Factory $queryBuilderFactory,
        protected Factory $queryExecutorFactory,
    ) {
    }

    /**
     * Return the active connection.
     */
    public function getConnection(): PdoConnection
    {
        return $this->connection;
    }

    public function query(): QueryBuilder
    {
        return $this->queryBuilderFactory->make();
    }

    /**
     * @param array<int|string,mixed> $parameters
     */
    public function execute(
        QueryBuilderInterface|string $query,
        array $parameters = []
    ): PdoQueryExecutor {
        return $this->queryExecutorFactory->make(
            $this->connection
        )->execute(
            $query,
            $parameters,
        );
    }
}
