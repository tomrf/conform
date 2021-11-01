<?php

namespace Tomrf\Snek;

use PDO;

final class Connection
{
    private PDO $pdo;
    private bool $isConnected = false;

    public function __construct(
        private QueryBuilderFactory $queryBuilderFactory,
        private string $dsn,
        private ?string $username = null,
        private ?string $password = null,
        private ?array $options = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]
    ) {
        $this->connect();
    }

    public static function DSN(
        string $host,
        string $dbname,
        int $port = 3306,
        string $driver = 'mysql',
        string $charset = 'utf8mb4'
    ): string {
        return sprintf(
            '%s:host=%s;dbname=%s;port=%d;charset=%s',
            $driver,
            $host,
            $dbname,
            $port,
            $charset
        );
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function queryTable(string $table): QueryBuilder
    {
        $queryBuilder = $this->queryBuilderFactory->makeQueryBuilder($this);

        return $queryBuilder->forTable($table);
    }

    private function connect(): void
    {
        $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        $this->isConnected = true;
    }
}
