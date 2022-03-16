<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use Tomrf\Conform\Data\Row;

interface QueryExecuterFactoryInterface
{
    public function execute(QueryBuilderInterface $queryBuilder): QueryExecuterInterface;

    public function findOne(): ?Row;

    public function findMany(): array;

    public function make(mixed ...$params): QueryExecuterInterface;
}
