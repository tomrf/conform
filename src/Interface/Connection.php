<?php

declare(strict_types=1);

namespace Tomrf\Snek\Interface;

use Tomrf\Snek\ActiveRecord\Model;
use Tomrf\Snek\ConnectionCredentials;
use Tomrf\Snek\QueryBuilder;

/** @package Connection */
interface Connection
{
    public function getQueryBuilder(): QueryBuilder;

    public function getCredentials(): ConnectionCredentials;

    /** @return null|array<int>  */
    public function getOptions(): ?array;

    public function isConnected(): bool;

    public function queryTable(string $tableName): QueryBuilder;

    public function persist(Model $model): Model;
}
