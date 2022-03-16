<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use Tomrf\Conform\Interface\ConnectionInterface;
use Tomrf\Conform\Interface\QueryBuilderFactoryInterface;
use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Interface\QueryExecuterFactoryInterface;
use Tomrf\Conform\Interface\QueryExecuterInterface;

class Conform
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected QueryBuilderFactoryInterface $queryBuilderFactory,
        protected QueryExecuterFactoryInterface $queryExecutorFactory,
    ) {
    }

    /**
     * Return the active connection.
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function query(): QueryBuilderFactoryInterface
    {
        return $this->queryBuilderFactory;
    }

    public function execute(QueryBuilderInterface $queryBuilder): QueryExecuterInterface
    {
        return $this->queryExecutorFactory->execute(
            $queryBuilder
        );
    }
}
