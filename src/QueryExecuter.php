<?php

namespace Tomrf\Snek;

use PDO;
use PDOStatement;

class QueryExecuter
{
    public function __construct(
        protected PdoConnection $connection,
    ) {
    }

    public function findOne(string $query, array $queryParameters): Row|bool
    {
        $this->limit = 1;

        $statement = $this->executeQuery($query, $queryParameters);
        $row = $this->fetchRow($statement);

        if (false === $row) {
            return false;
        }

        return $row;
    }

    public function findMany(string $query, array $queryParameters): ?array // @todo RowCollection
    {
        $statement = $this->executeQuery($query, $queryParameters);

        return $this->fetchAllRows($statement);
    }

    protected function executeQuery(string $query, array $queryParameters): PDOStatement
    {
        try {
            $statement = $this->connection->getPdo()->prepare(
                $query
            );
        } catch (\Exception $e) {
            throw new \Exception('Error preparing statement: '.$e->getMessage());
        }

        try {
            $statement->execute($queryParameters);
        } catch (\Exception $e) {
            throw new \Exception('Error executing query: '.$e->getMessage());
        }

        return $statement;
    }

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

    protected function fetchRow(PDOStatement $statement)
    {
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return (false === $row) ? false : new Row($row);
    }
}
