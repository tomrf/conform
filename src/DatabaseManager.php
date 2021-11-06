<?php

namespace Tomrf\Snek;

use RuntimeException;

class DatabaseManager
{
    protected array $connections = [];

    public function __construct(
    ) {
    }

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

    // public function makeQueryBuilder(
    //     PdoConnection $connection,
    //     ?ModelFactory $modelFactory = null,
    //     ?string $modelClass = null
    // ): object // @todo QueryBuilderInterface
    // {
    //     return new $this->queryBuilderClass(
    //         new $this->queryExecuterClass(
    //             $connection,
    //             $modelFactory,
    //             $modelClass
    //         )
    //     );
    // }
}
