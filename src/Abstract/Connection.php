<?php

declare(strict_types=1);

namespace Tomrf\Conform\Abstract;

use Tomrf\Conform\ActiveRecord\Model;
use Tomrf\Conform\Interface\ConnectionInterface;

/** @package Connection */
abstract class Connection implements ConnectionInterface
{
    abstract public function getQueryBuilder(): QueryBuilder;

    abstract public function getCredentials(): ConnectionCredentials;

    /** @return null|array<int>  */
    abstract public function getOptions(): ?array;

    abstract public function isConnected(): bool;

    abstract public function queryTable(string $tableName): QueryBuilder;

    abstract public function persist(Model $model): Model;
}
