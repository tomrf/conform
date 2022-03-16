<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use PDO;
use PDOStatement;
use Tomrf\Conform\Pdo\PdoConnectionCredentials;

interface ConnectionInterface
{
    public function exec(string $statement): int|false;

    public function query(string $statement): PDOStatement|false;

    public function getPdo(): PDO;

    public function getCredentials(): PdoConnectionCredentials;

    public function getOptions(): ?array;

    public function isConnected(): bool;
}
