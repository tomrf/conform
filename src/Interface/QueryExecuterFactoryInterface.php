<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use Tomrf\Conform\Data\Row;

interface QueryExecuterFactoryInterface extends FactoryInterface
{
    public function execute(ConnectionInterface $connection, QueryBuilderInterface $queryBuilder): QueryExecuterInterface;

    public function findOne(): ?Row;

    /**
     * Fetch all rows from query result set.
     *
     * @return array<int,Row>
     */
    public function findMany(): array;
}
