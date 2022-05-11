<?php

declare(strict_types=1);

namespace Tomrf\Conform\Pdo;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Tomrf\Conform\Data\NullValue;
use Tomrf\Conform\Data\Row;
use Tomrf\Conform\Data\Value;
use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Interface\QueryExecutorInterface;

class PdoQueryExecutor implements QueryExecutorInterface
{
    protected PDOStatement $pdoStatement;

    public function __construct(
        protected PdoConnection $connection
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
    public function getLastInsertId(): string|false
    {
        return $this->connection->getPdo()?->lastInsertId() ?? false;
    }

    /**
     * Prepare and execute PDOStatement from an instance of
     * QueryBuilderInterface.
     *
     * @throws PDOException
     */
    public function execute(
        QueryBuilderInterface|string $query,
        array $parameters = []
    ): static {
        if ($query instanceof QueryBuilderInterface) {
            $this->pdoStatement = $this->executeQuery(
                $query->getQuery(),
                $query->getQueryParameters()
            );

            return $this;
        }

        $this->pdoStatement = $this->executeQuery(
            $query,
            $parameters
        );

        return $this;
    }

    /**
     * Fetch next row from the result set as Row.
     */
    public function findOne(): ?Row
    {
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
        if (true !== $this->connection->isConnected()) {
            throw new RuntimeException(
                'Unable to execute query - not connected to database'
            );
        }

        $statement = $this->connection->getPdo()?->prepare(
            $query
        );

        if ($statement instanceof PDOStatement) {
            $statement->execute($queryParameters);
        } else {
            throw new RuntimeException(
                'Could not get prepared statement, connection error?'
            );
        }

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
            if (null === $value) {
                $values[(string) $key] = new NullValue();
            } else {
                $values[(string) $key] = new Value($value);
            }
        }

        return new Row($values);
    }
}
