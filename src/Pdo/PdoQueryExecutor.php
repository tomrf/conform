<?php

declare(strict_types=1);

namespace Tomrf\Conform\Pdo;

use PDO;
use PDOException;
use PDOStatement;
use Tomrf\Conform\Data\NullValue;
use Tomrf\Conform\Data\Row;
use Tomrf\Conform\Data\Value;
use Tomrf\Conform\Interface\ConnectionInterface;
use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Interface\QueryExecutorInterface;

class PdoQueryExecutor implements QueryExecutorInterface
{
    protected PDOStatement $pdoStatement;

    public function __construct(
        protected ConnectionInterface $connection
    ) {
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     */
    public function getRowCount(): int
    {
        return $this->pdoStatement->rowCount();
    }

    /**
     * Returns the last inserted row ID as string.
     */
    public function getLastInsertId(): string
    {
        return $this->connection->getPdo()->lastInsertId();
    }

    // /**
    //  * Prepare and execute PDOStatement from query string and array of
    //  * parameters.
    //  *
    //  * @param array<string,mixed> $queryParameters
    //  *
    //  * @throws PDOException
    //  */
    // public function execute(string $query, array $queryParameters): static
    // {
    //     $this->pdoStatement = $this->executeQuery($query, $queryParameters);
    //     return $this;
    // }

    /**
     * Prepare and execute PDOStatement from an instance of
     * QueryBuilderInterface.
     *
     * @throws PDOException
     */
    public function execute(QueryBuilderInterface|string $query, array $parameters = []): static
    {
        if ($query instanceof QueryBuilderInterface) {
            $this->pdoStatement = $this->executeQuery(
                $query->getQuery(),
                $query->getQueryParameters()
            );
        } else {
            $this->pdoStatement = $this->executeQuery(
                $query,
                $parameters
            );
        }

        return $this;
    }

    /**
     * Fetch next row from the result set as Row.
     */
    public function findOne(): ?Row
    {
        // $this->then__execute($this);

        $row = $this->fetchRow($this->pdoStatement);

        if (false === $row) {
            return null;
        }

        return $row;
    }

    /**
     * Fetch all rows from query result set.
     *
     * @return array<int,Row>
     */
    public function findMany(): array
    {
        return $this->fetchAllRows($this->pdoStatement);
    }

    /**
     * Prepare and execute PDOStatement from query string and array of
     * parameters.
     *
     * @param array<int|string,mixed> $queryParameters
     *
     * @throws PDOException
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
     * Fetch all rows in a result set as array of Row.
     *
     * @return array<int,Row>
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

    /**
     * Fetch next row from result set as Row.
     */
    protected function fetchRow(PDOStatement $statement): Row|false
    {
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (false === $row) {
            return false;
        }

        $values = [];

        foreach ($row as $key => $value) {
            // @todo keep key/column name in Value .. or Row?
            if (null === $value) {
                $values[(string) $key] = new NullValue();
            } else {
                $values[(string) $key] = new Value($value);
            }
        }

        return new Row($values);
    }
}