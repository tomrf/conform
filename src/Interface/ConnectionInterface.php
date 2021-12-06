<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use Tomrf\Conform\Abstract\ConnectionCredentials;
use Tomrf\Conform\Abstract\QueryBuilder;
use Tomrf\Conform\ActiveRecord\Model;

/** @package Connection */
interface ConnectionInterface
{
    public function getQueryBuilder(): QueryBuilder;

    public function getCredentials(): ConnectionCredentials;

    /** @return null|array<int>  */
    public function getOptions(): ?array;

    public function isConnected(): bool;

    public function queryTable(string $tableName): QueryBuilder;

    public function persist(Model $model): Model;
}
