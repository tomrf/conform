<?php

declare(strict_types=1);

namespace Tomrf\Snek\Interface;

use Tomrf\Snek\Row;

interface QueryBuilderInterface
{
    public function forTable(string $table): self;

    public function select(string ...$params): self;

    public function selectAs(string $expression, string $alias): self;

    public function selectRaw(string ...$params): self;

    public function selectRawAs(string $expression, string $alias): self;

    public function alias(string $expression, string $alias): self;

    public function join(string $table, string $joinCondition): self;

    public function where(string $column, string $operator, mixed $value): self;

    public function whereEqual(string $column, mixed $value): self;

    public function whereNotEqual(string $column, mixed $value): self;

    public function whereNull(string $column): self;

    public function whereNotNull(string $column): self;

    /**
     * @param null|array<string, mixed> $namedParameters
     */
    public function whereRaw(string $clause, ?array $namedParameters = null): self;

    public function orderByAsc(string $column): self;

    public function orderByDesc(string $column): self;

    public function limit(int $limit, ?int $offset = null): self;

    public function offset(int $offset, ?int $limit = null): self;

    public function findOne(): Row|bool;

    /**
     * @return null|array<int,mixed>
     */
    public function findMany(): ?array;
}
