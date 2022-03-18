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

    /**
     * Get PDO options array for this connections.
     *
     * @return null|array<int, int>
     */
    public function getOptions(): ?array;

    public function isConnected(): bool;
}
