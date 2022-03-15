<?php

declare(strict_types=1);

namespace Tomrf\Conform\Pdo;

use PDO;
use PDOStatement;
use RuntimeException;
use Tomrf\Conform\Factory;

class PdoConnection
{
    protected PDO $pdo;
    protected bool $isConnected = false;

    /**
     * @param null|array<int,int> $options
     */
    public function __construct(
        protected PdoConnectionCredentials $credentials,
        protected Factory $queryBuilderFactory,
        protected Factory $queryExecuterFactory,
        protected ?array $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    ) {
        $this->connect();
    }

    /**
     * Create and return a query builder.
     */
    public function makeQueryBuilder(): PdoQueryBuilder
    {
        return $this->queryBuilderFactory->make(
            $this->queryExecuterFactory->make(
                $this,
            )
        );
    }

    public function exec(string $statement): int|false
    {
        return $this->pdo->exec($statement);
    }

    /**
     * Execute statement.
     */
    public function query(string $statement): PDOStatement|false
    {
        return $this->pdo->query($statement);
    }

    /**
     * Get the PDO resource object for this connection.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get the PdoConnectionCredentials for this connection.
     */
    public function getCredentials(): PdoConnectionCredentials
    {
        return $this->credentials;
    }

    /**
     * Get PDO options array.
     *
     * @return null|array<int, int>
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * Check if connected to database.
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    protected function connect(): void
    {
        try {
            $this->pdo = new PDO(
                $this->credentials->getDsn(),
                $this->credentials->getUsername(),
                $this->credentials->getPassword(),
                $this->options
            );
        } catch (\PDOException $exception) {
            throw new RuntimeException(
                sprintf('Unable to connect to database: %s', $exception)
            );
        }

        $this->isConnected = true;
    }
}
