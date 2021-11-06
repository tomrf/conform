<?php

namespace Tomrf\Snek;

use PDO;

class PdoConnection extends Connection
{
    protected PDO $pdo;
    protected bool $isConnected = false;

    public function __construct(
        protected Credentials $credentials,
        protected Factory $queryBuilderFactory,
        protected Factory $queryExecuterFactory,
        protected ?array $options = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]
    ) {
        $this->connect();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilderFactory->make(
            $this->queryExecuterFactory->make(
                $this,
            )
        );
    }

    public function queryTable(string $tableName): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder->forTable($tableName);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getCredentials(): Credentials
    {
        return $this->credentials;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    protected function connect(): void
    {
        $this->pdo = new PDO(
            $this->credentials->getDsn(),
            $this->credentials->getUsername(),
            $this->credentials->getPassword(),
            $this->options
        );

        $this->isConnected = true;
    }
}