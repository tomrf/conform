<?php

declare(strict_types=1);

namespace Tomrf\Conform\Abstract;

use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Row;

abstract class QueryBuilder implements QueryBuilderInterface
{
    abstract public function forTable(string $table): self;

    /**
     * @param array<string, mixed> $keyValue
     */
    abstract public function insert(array $keyValue): string|false;

    abstract public function select(string ...$params): self;

    abstract public function selectAs(string $expression, string $alias): self;

    abstract public function selectRaw(string ...$params): self;

    abstract public function selectRawAs(string $expression, string $alias): self;

    abstract public function alias(string $expression, string $alias): self;

    abstract public function join(string $table, string $joinCondition): self;

    abstract public function where(string $column, string $operator, mixed $value): self;

    abstract public function whereEqual(string $column, mixed $value): self;

    abstract public function whereNotEqual(string $column, mixed $value): self;

    abstract public function whereNull(string $column): self;

    abstract public function whereNotNull(string $column): self;

    /**
     * @param null|array<string, mixed> $namedParameters
     *
     * @return QueryBuilder
     */
    abstract public function whereRaw(string $clause, ?array $namedParameters = null): self;

    abstract public function orderByAsc(string $column): self;

    abstract public function orderByDesc(string $column): self;

    abstract public function limit(int $limit, ?int $offset = null): self;

    abstract public function offset(int $offset, ?int $limit = null): self;

    abstract public function findOne(): Row|bool;

    /**
     * @return null|array<int,mixed>
     */
    abstract public function findMany(): ?array;
}
