<?php

declare(strict_types=1);

namespace Tomrf\Conform\Bridge\Pdo;

use DomainException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Tomrf\Conform\Abstract\Connection;
use Tomrf\Conform\Abstract\ConnectionCredentials;
use Tomrf\Conform\Abstract\QueryBuilder;
use Tomrf\Conform\ActiveRecord\Model;
use Tomrf\Conform\Factory;
use Tomrf\Conform\Interface\ConnectionInterface;

class PdoConnection extends Connection implements ConnectionInterface
{
    protected PDO $pdo;
    protected bool $isConnected = false;

    /**
     * @param ConnectionCredentials $credentials
     * @param Factory               $queryBuilderFactory
     * @param Factory               $queryExecuterFactory
     * @param null|array<int,int>   $options
     */
    public function __construct(
        protected ConnectionCredentials $credentials,
        protected Factory $queryBuilderFactory,
        protected Factory $queryExecuterFactory,
        protected ?array $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    ) {
        $this->connect();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilderFactory->make(
            $this->queryExecuterFactory->make(
                $this,
            )
        );
    }

    public function queryTable(string $tableName): PdoQueryBuilder
    {
        /** @var PdoQueryBuilder */
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder->forTable($tableName);
    }

    public function exec(string $statement): int|false
    {
        return $this->pdo->exec($statement);
    }

    public function query(string $statement): PDOStatement|false
    {
        return $this->pdo->query($statement);
    }

    public function persist(Model $model): Model
    {
        if (null === $model->getPrimaryKey()) {
            throw new DomainException('Cannot persist model: primary key in data is NULL');
        }

        if (!$model->isDirty()) {
            return $model;
        }

        $parameters = [];
        $query = sprintf('UPDATE `%s` SET ', $model->getTable());
        foreach ($model->getDirty() as $column => $value) {
            $parameterName = $column;
            if (isset($parameters[$parameterName])) {
                $parameterName = $parameterName.mb_substr(md5(random_bytes(32)), 0, 6);
            }
            $query .= sprintf('`%s`=:%s', $column, $parameterName);
            if ($column !== array_key_last($model->getDirty())) {
                $query .= ', ';
            }
            $parameters[$parameterName] = $value;
        }

        $query .= sprintf(' WHERE `%s`=:%s', $model->getPrimaryKeyColumn(), $model->getPrimaryKeyColumn());
        $parameters[$model->getPrimaryKeyColumn()] = $model->getPrimaryKey();

        try {
            $statement = $this->getPdo()->prepare($query);
            $statement->execute($parameters);
        } catch (PDOException $e) {
            throw new RuntimeException(sprintf(
                'Could not persist model: %s',
                $e->getMessage()
            ));
        }

        $modelClass = \get_class($model);

        return new $modelClass($model->toArray(true));
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getCredentials(): ConnectionCredentials
    {
        return $this->credentials;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    protected function connect(): void
    {
        try {
            $this->pdo = new PDO(
                $this->credentials->getDsn(),
                $this->credentials->getUsername(),
                $this->credentials->getPassword(),
                $this->options
            );
        } catch (\PDOException $exception) {
            throw new RuntimeException(
                sprintf('Unable to connect to database: %s', $exception)
            );
        }

        $this->isConnected = true;
    }
}
