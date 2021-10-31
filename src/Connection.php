<?php

namespace Tomrf\Snek;

use PDO;

final class Connection
{
    private PDO $pdo;
    private bool $isConnected = false;
    private array $tableClassMap = [];
    private array $classTableMap = [];

    function __construct(
        private string $dsn,
        private ?string $username = null,
        private ?string $password = null,
        private ?array $options = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]
    ) {
        $this->connect();
    }

    static public function DSN(
        string $driver = 'mysql',
        string $dbname,
        string $host,
        int $port = 3306,
        string $charset = 'utf8mb4'
    ): string {
        return sprintf(
            '%s:host=%s;dbname=%s;port=%d;charset=%s',
            $driver, $host, $dbname, $port, $charset
        );
    }

    private function connect(): void
    {
        $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        $this->isConnected = true;
    }

    public function getClassForTable(string $table): ?string
    {
        return $this->tableClassMap[$table] ?? null;
    }

    public function getTableForClass(string $class): ?string
    {
        return $this->classTableMap[$class] ?? null;
    }

    public function setTableClass(string $table, string $class): void
    {
        $this->tableClassMap[$table] = $class;
        $this->classTableMap[$class] = $table;
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

    public function forTable(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

}
