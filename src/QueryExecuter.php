<?php

namespace Tomrf\Snek;

use PDO;
use PDOStatement;

class QueryExecuter
{
    public function __construct(
        private Connection $connection,
    ) {}

    private function executeQuery(string $query, array $queryParameters): PDOStatement
    {
        try {
            $statement = $this->connection->getPdo()->prepare(
                $query
            );
        } catch (\Exception $e) {
            throw new \Exception('Error preparing statement: ' . $e->getMessage());
        }

        try {
            $statement->execute($queryParameters);
        } catch (\Exception $e) {
            throw new \Exception('Error executing query: ' . $e->getMessage());
        }

        return $statement;
    }

    private function fetchAllRows(PDOStatement $statement): array
    {
        for ($rows = [];;) {
            if (($row = $this->fetchRow($statement)) === false) {
                break;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function fetchRow(PDOStatement $statement)
    {
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return ($row === false) ? false : new Row($row);
    }

    public function findOne(string $query, array $queryParameters): Row|bool
    {
        $this->limit = 1;

        $statement = $this->executeQuery($query, $queryParameters);
        $row = $this->fetchRow($statement);

        if ($row === false) {
            return false;
        }

        return $row;
    }

    public function findMany(string $query, array $queryParameters): ?array /* @todo RowCollection */
    {
        $statement = $this->executeQuery($query, $queryParameters);
        return $this->fetchAllRows($statement);
    }
}
