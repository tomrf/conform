<?php

namespace Tomrf\Snek;

abstract class QueryBuilder
{
    abstract public function forTable(string $table): self;

    abstract public function select(...$params): self;

    abstract public function selectAs(string $expression, string $alias): self;

    abstract public function selectRaw(...$params): self;

    abstract public function selectRawAs(string $expression, string $alias): self;

    abstract public function alias(string $expression, string $alias): self;

    abstract public function join(string $table, string $joinCondition): self;

    abstract public function where(string $column, string $operator, mixed $value): self;

    abstract public function whereEqual(string $column, mixed $value): self;

    abstract public function whereNotEqual(string $column, mixed $value): self;

    abstract public function whereNull(string $column): self;

    abstract public function whereNotNull(string $column): self;

    abstract public function whereRaw(string $clause, ?array $namedParameters = null): self;

    abstract public function orderByAsc(string $column): self;

    abstract public function orderByDesc(string $column): self;

    abstract public function limit(int $limit, ?int $offset = null): self;

    abstract public function offset(int $offset, ?int $limit = null): self;

    abstract public function findOne(): Row|bool;

    abstract public function findMany(): ?array;

    abstract protected function getQueryParameters(): array;

    abstract protected function buildQuerySelectExpression(): string;

    abstract protected function buildQueryJoinClause(): string;

    abstract protected function buildQueryWhereCondition(): string;

    abstract protected function buildQueryOrderByClause(): string;

    abstract protected function buildQuery(): string;

    abstract protected function assertQueryState(): void;

    abstract protected function quoteString(string $string): string;

    abstract protected function quoteExpression(string $expression): string;

    abstract protected function isExpressionQuoted(string $expression): bool;
}
