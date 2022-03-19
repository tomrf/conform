<?php

declare(strict_types=1);

namespace Tomrf\Conform\Pdo;

use PDO;
use RuntimeException;
use Tomrf\Conform\Interface\ConnectionInterface;

class PdoConnection implements ConnectionInterface
{
    protected PDO $pdo;
    protected bool $isConnected = false;

    /**
     * @param null|array<int,int> $options
     */
    public function __construct(
        protected string $dsn,
        protected ?string $username = null,
        protected ?string $password = null,
        protected ?array $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    ) {
        $this->connect();
    }

    /**
     * Get the PDO resource object for this connection.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get PDO options array for this connection.
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
     * Get the value of dsn.
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * Get the value of username.
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Static helper function to build DSN string for PDO.
     */
    public static function DSN(
        string $driver,
        string $dbname,
        string $host = null,
        int $port = 3306,
        string $charset = 'utf8mb4'
    ): string {
        if ('sqlite' === mb_strtolower($driver)) {
            $dsn = sprintf('%s:%s', $driver, $dbname);
        } else {
            $dsn = sprintf(
                '%s:host=%s;dbname=%s;port=%d;charset=%s',
                $driver,
                $host,
                $dbname,
                $port,
                $charset
            );
        }

        return $dsn;
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
                $this->dsn,
                $this->username,
                $this->password,
                $this->options
            );
        } catch (\PDOException $exception) {
            throw new RuntimeException(
                sprintf('Unable to connect to database: %s', $exception)
            );
        }

        $this->isConnected = true;
        $this->password = null;
    }
}
