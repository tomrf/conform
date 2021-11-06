<?php

namespace Tomrf\Snek;

class Credentials
{
    public function __construct(
        protected string $dsn,
        protected ?string $username = null,
        protected ?string $password = null
    ) {
    }

    /**
     * Get the value of dsn.
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * Get the value of username.
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the value of password.
     */
    public function getPassword()
    {
        return $this->password;
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
}
