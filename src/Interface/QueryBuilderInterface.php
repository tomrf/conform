<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

interface QueryBuilderInterface
{
    public function selectFrom(string $table): static;

    public function insertInto(string $table): static;

    public function update(string $table): static;

    public function deleteFrom(string $table): static;

    public function set(string $column, mixed $value): static;

    public function setRaw(string $column, string $expression): static;

    public function select(string ...$columns): static;

    public function selectAs(string $expression, string $alias): static;

    public function selectRaw(string ...$params): static;

    public function selectRawAs(string $expression, string $alias): static;

    public function alias(string $expression, string $alias): static;

    public function join(string $table, string $joinCondition): static;

    public function whereRaw(string $expression): static;

    public function whereColumnRaw(string $column, string $expression): static;

    public function where(string $column, string $operator, mixed $value): static;

    public function whereEqual(string $column, mixed $value): static;

    public function whereNotEqual(string $column, mixed $value): static;

    public function whereNull(string $column): static;

    public function whereNotNull(string $column): static;

    public function orderByAsc(string $column): static;

    public function orderByDesc(string $column): static;

    public function limit(int $limit, ?int $offset = null): static;

    public function offset(int $offset): static;

    public function onDuplicateKey(string $expression): static;

    public function getQuery(): string;

    /**
     * @return array<string,mixed>
     */
    public function getQueryParameters(): array;
}
