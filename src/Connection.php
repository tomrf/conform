<?php

declare(strict_types=1);

namespace Tomrf\Snek;

/** @package Connection */
abstract class Connection
{
    abstract public function getQueryBuilder(): QueryBuilder;

    abstract public function getCredentials(): ConnectionCredentials;

    /** @return null|array<int>  */
    abstract public function getOptions(): ?array;

    abstract public function isConnected(): bool;

    abstract public function queryTable(string $tableName): QueryBuilder;

    abstract public function persist(Model $model): Model;

    abstract protected function connect(): void;
}
