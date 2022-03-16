<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use Tomrf\Conform\Data\Row;

interface QueryExecuterInterface
{
    public function getRowCount(): int;

    public function getLastInsertId(): string;

    // public function execute(string $query, array $queryParameters): static;
    public function execute(QueryBuilderInterface $queryBuilder): static;

    public function findOne(): ?Row;

    public function findMany(): array;
}
