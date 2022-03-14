<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use RuntimeException;
use Tomrf\Conform\Pdo\PdoConnection;

class ConnectionManager
{
    /**
     * @var array<PdoConnection>
     */
    protected array $connections = [];

    /**
     * @throws RuntimeException
     */
    public function addConnection(PdoConnection $connection, string $name = 'default'): void
    {
        if (isset($this->connections[$name])) {
            throw new RuntimeException(sprintf('A connection named %s already exists', $name));
        }
        $this->connections[$name] = $connection;
    }

    public function getConnection(string $name = 'default'): PdoConnection
    {
        return $this->connections[$name] ?? null;
    }
}
