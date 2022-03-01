<?php

declare(strict_types=1);

namespace Tomrf\Conform\Bridge\Pdo;

use Exception;
use PDO;
use PDOStatement;
use Tomrf\Conform\Abstract\QueryExecuter;
use Tomrf\Conform\Row;

class PdoQueryExecuter extends QueryExecuter
{
    public function __construct(
        protected PdoConnection $connection,
    ) {
    }

    /**
     * @param array<string,mixed> $queryParameters
     */
    public function insert(string $query, array $queryParameters): string|false
    {
        $this->executeQuery($query, $queryParameters);

        return $this->connection->getPdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $queryParameters
     *
     * @throws Exception
     */
    public function findOne(string $query, array $queryParameters): Row|bool
    {
        $statement = $this->executeQuery($query, $queryParameters);
        $row = $this->fetchRow($statement);

        if (false === $row) {
            return false;
        }

        return $row;
    }

    /**
     * @param array<string,mixed> $queryParameters
     *
     * @throws Exception
     *
     * @return array<int,mixed>
     */
    public function findMany(string $query, array $queryParameters): array
    {
        $statement = $this->executeQuery($query, $queryParameters);

        return $this->fetchAllRows($statement);
    }

    /**
     * @param array<string,mixed> $queryParameters
     *
     * @throws Exception
     */
    protected function executeQuery(string $query, array $queryParameters): PDOStatement
    {
        $statement = $this->connection->getPdo()->prepare(
            $query
        );

        $statement->execute($queryParameters);

        return $statement;
    }

    /**
     * @return array<int,mixed>
     */
    protected function fetchAllRows(PDOStatement $statement): array
    {
        for ($rows = [];;) {
            if (($row = $this->fetchRow($statement)) === false) {
                break;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function fetchRow(PDOStatement $statement): Row|false
    {
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return (false === $row) ? false : new Row($row);
    }
}
