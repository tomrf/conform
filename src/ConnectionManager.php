<?php

declare(strict_types=1);

namespace Tomrf\Snek;

use RuntimeException;

class ConnectionManager
{
    /**
     * @var array<Connection>
     */
    protected array $connections = [];

    /**
     * @throws RuntimeException
     */
    public function addConnection(Connection $connection, string $name = 'default'): void
    {
        if (isset($this->connections[$name])) {
            throw new RuntimeException(sprintf('A connection named %s already exists', $name));
        }
        $this->connections[$name] = $connection;
    }

    public function getConnection(string $name = 'default'): Connection
    {
        return $this->connections[$name] ?? null;
    }
}
