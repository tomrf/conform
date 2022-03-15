<?php

declare(strict_types=1);

namespace Tomrf\Conform\Pdo;

use Exception;
use PDO;
use PDOStatement;
use Tomrf\Conform\Row;

class PdoQueryExecuter
{
    protected PDOStatement $pdoStatement;

    public function __construct(
        protected PdoConnection $connection,
    ) {
    }

    public function getRowCount(): int
    {
        return $this->pdoStatement->rowCount();
    }

    public function getLastInsertId(): string
    {
        return $this->connection->getPdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $queryParameters
     *
     * @throws Exception
     */
    public function execute(string $query, array $queryParameters): static
    {
        $this->pdoStatement = $this->executeQuery($query, $queryParameters);

        return $this;
    }

    public function findOne(): Row|bool
    {
        $row = $this->fetchRow($this->pdoStatement);

        if (false === $row) {
            return false;
        }

        return $row;
    }

    /**
     * @return array<int,mixed>
     */
    public function findMany(): array
    {
        return $this->fetchAllRows($this->pdoStatement);
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
