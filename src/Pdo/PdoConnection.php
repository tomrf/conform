<?php

declare(strict_types=1);

namespace Tomrf\Conform\Pdo;

use PDO;
use PDOStatement;
use RuntimeException;

class PdoConnection
{
    protected PDO $pdo;
    protected bool $isConnected = false;

    /**
     * @param null|array<int,int> $options
     */
    public function __construct(
        protected PdoConnectionCredentials $credentials,
        protected ?array $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    ) {
        $this->connect();
    }

    /**
     * Execute SQL statement via PDO exec() and return number of rows affected.
     */
    public function exec(string $statement): int|false
    {
        return $this->pdo->exec($statement);
    }

    /**
     * Execute statement via PDO query(), returning a result set as
     * PDOStatement.
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
     * Get PDO options array for this connections.
     *
     * @return null|array<int, int>
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * Returns true if database connection has been established.
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Connect to the database.
     *
     * @throws RuntimeException
     */
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
