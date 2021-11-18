<?php

namespace Tomrf\Snek;

abstract class QueryBuilder
{
    abstract public function forTable(string $table): QueryBuilder;

    abstract public function select(...$params): QueryBuilder;

    abstract public function selectAs(string $expression, string $alias): QueryBuilder;

    abstract public function selectRaw(...$params): QueryBuilder;

    abstract public function selectRawAs(string $expression, string $alias): QueryBuilder;

    abstract public function alias(string $expression, string $alias): QueryBuilder;

    abstract public function join(string $table, string $joinCondition): QueryBuilder;

    abstract public function where(string $column, string $operator, mixed $value): QueryBuilder;

    abstract public function whereEqual(string $column, mixed $value): QueryBuilder;

    abstract public function whereNotEqual(string $column, mixed $value): QueryBuilder;

    abstract public function whereNull(string $column): QueryBuilder;

    abstract public function whereNotNull(string $column): QueryBuilder;

    abstract public function whereRaw(string $clause, ?array $namedParameters = null): QueryBuilder;

    abstract public function orderByAsc(string $column): QueryBuilder;

    abstract public function orderByDesc(string $column): QueryBuilder;

    abstract public function limit(int $limit, ?int $offset = null): QueryBuilder;

    abstract public function offset(int $offset, ?int $limit = null): QueryBuilder;

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
