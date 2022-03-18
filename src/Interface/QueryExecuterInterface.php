<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use Tomrf\Conform\Data\Row;

interface QueryExecuterInterface
{
    public function getRowCount(): int;

    public function getLastInsertId(): string;

    /**
     * @param array<int|string,mixed> $parameters
     */
    public function execute(QueryBuilderInterface|string $query, array $parameters = []): static;

    public function findOne(): ?Row;

    /**
     * @return array<int,Row>
     */
    public function findMany(): array;
}
