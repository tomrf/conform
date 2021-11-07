<?php

namespace Tomrf\Snek;

abstract class QueryBuilder
{
    abstract public function forTable(string $table): QueryBuilder;

    abstract public function select(...$params): QueryBuilder;

    abstract public function selectAs(string $expression, string $alias): QueryBuilder;

    abstract public function join(string $table, string $joinCondition): QueryBuilder;

    abstract public function alias(string $expression, string $alias): QueryBuilder;

    abstract public function where(string $key, mixed $value): QueryBuilder;

    abstract public function orderByAsc(string $column): QueryBuilder;

    abstract public function orderByDesc(string $column): QueryBuilder;

    abstract public function limit(int $limit, ?int $offset = null): QueryBuilder;

    abstract public function offset(int $offset, ?int $limit = null): QueryBuilder;

    abstract public function findOne(): Row|bool;

    abstract public function findMany(): ?array;
}
