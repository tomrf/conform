<?php

declare(strict_types=1);

namespace Tomrf\Conform\Factory;

use Tomrf\Conform\Data\Row;
use Tomrf\Conform\Interface\ConnectionInterface;
use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Interface\QueryExecuterFactoryInterface;
use Tomrf\Conform\Interface\QueryExecuterInterface;

class QueryExecuterFactory extends Factory implements QueryExecuterFactoryInterface
{
    protected ConnectionInterface $connection;

    public function execute(ConnectionInterface $connection, QueryBuilderInterface $queryBuilder): QueryExecuterInterface
    {
        $this->connection = $connection;

        return ($this->make($connection))->execute(
            $queryBuilder
        );
    }

    public function findOne(): ?Row
    {
        return ($this->make($this->connection))->findOne();
    }

    /**
     * Fetch all rows from query result set.
     *
     * @return array<int,Row>
     */
    public function findMany(): array
    {
        return ($this->make($this->connection))->findMany();
    }
}
