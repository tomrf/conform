<?php

namespace Tomrf\Snek\Pdo;

use Tomrf\Snek\Credentials;

class PdoCredentials extends Credentials
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
        string $driver = 'mysql',
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
}
