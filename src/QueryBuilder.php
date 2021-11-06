<?php

namespace Tomrf\Snek;

abstract class QueryBuilder
{
    abstract public function forTable(string $table): QueryBuilder;

    abstract public function select(string $name, string $alias = null): QueryBuilder;

    abstract public function join(string $table, string $joinCondition): QueryBuilder;

    abstract public function where(string $key, mixed $value): QueryBuilder;

    abstract public function orderByAsc(string $column): QueryBuilder;

    abstract public function orderByDesc(string $column): QueryBuilder;

    abstract public function limit(int $limit, ?int $offset = null): QueryBuilder;

    abstract public function offset(int $offset, ?int $limit = null): QueryBuilder;

    abstract public function findOne(): Row|bool;

    abstract public function findMany(): ?array;
}
