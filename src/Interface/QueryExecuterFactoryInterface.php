<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use Tomrf\Conform\Data\Row;

interface QueryExecuterFactoryInterface extends FactoryInterface
{
    /**
     * @param array<int|string,mixed> $parameters
     */
    public function execute(
        ConnectionInterface $connection,
        QueryBuilderInterface|string $query,
        array $parameters = []
    ): QueryExecuterInterface;

    public function findOne(): ?Row;

    /**
     * Fetch all rows from query result set.
     *
     * @return array<int,Row>
     */
    public function findMany(): array;
}
