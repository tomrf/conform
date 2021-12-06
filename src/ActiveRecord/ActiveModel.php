<?php

declare(strict_types=1);

namespace Tomrf\Snek\ActiveRecord;

use ReflectionClass;
use RuntimeException;
use Tomrf\Snek\Abstract\Connection;
use Tomrf\Snek\Abstract\QueryBuilder;
use Tomrf\Snek\Row;

class ActiveModel extends Model
{
    /**
     * @ignore
     *
     * @param array<string,mixed>|Row $data
     */
    public function __construct(Row|array $data = [], protected ?Connection $connection = null)
    {
        $this->setDefaults();

        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function __sleep()
    {
        $array = [];

        $this->connection = null;

        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $array[] = $property->name;
        }

        return $array;
    }

    public static function new(?Connection $connection = null): self
    {
        return self::returnInstanceOfSelf([], $connection);
    }

    public static function fromRow(Row $row, ?Connection $connection = null): self
    {
        return self::returnInstanceOfSelf($row, $connection);
    }

    public static function fromObject(self|Model $modelObject, ?Connection $connection = null): self
    {
        return self::returnInstanceOfSelf($modelObject->toArray(true), $connection);
    }

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function withConnection(Connection $connection): self
    {
        $this->setConnection($connection);

        return $this;
    }

    public static function byPrimaryKey(Connection $connection, int|string $id): self
    {
        return self::byColumn($connection, (string) $id, self::getPrimaryKeyName());
    }

    public static function byColumn(Connection $connection, string $column, int|string $id): self
    {
        $row = $connection->getQueryBuilder()
            ->forTable(self::getTableName())
            ->whereEqual($column, $id)
            ->findOne()
        ;

        if (false === $row) {
            throw new RuntimeException(sprintf(
                'Could not create instance of model "%s" from database connection: no match for column "%s" with value "%s"',
                self::getTableName(),
                $column,
                (string) $id
            ));
        }

        return self::fromRow($row, $connection);
    }

    public function persist(?Connection $connection = null): bool
    {
        if (false === $this->onBeforePersist()) {
            // @todo throw ?
            return false;
        }

        if (null !== $connection) {
            $connection->persist($this);
        } elseif (null !== $this->connection) {
            $this->connection->persist($this);
        } else {
            throw new RuntimeException('Unable to persist: no database connection associated with model instance');
        }

        $this->flushDirty();

        return $this->onAfterPersist();
    }

    protected function belongsTo(string $modelClass, string $ownColumn = null): QueryBuilder
    {
        $reflection = new ReflectionClass($modelClass);

        /** @var Model */
        $model = $reflection->newInstanceWithoutConstructor();

        if (null === $ownColumn) {
            $ownColumn = $model->getTable().'_id';
        }

        return $this->connection->getQueryBuilder()
            ->forTable($model->getTable())
            ->whereEqual('id', $this->getAny($ownColumn))
        ;
    }

    protected function hasOne(string $modelClass, string $foreignColumn = null): QueryBuilder
    {
        $reflection = new ReflectionClass($modelClass);

        /** @var Model */
        $model = $reflection->newInstanceWithoutConstructor();

        if (null === $foreignColumn) {
            $foreignColumn = $this->getTable().'_id';
        }

        return $this->connection->getQueryBuilder()
            ->forTable($model->getTable())
            ->whereEqual($foreignColumn, $this->getPrimaryKey())
        ;
    }

    protected function hasMany(string $modelClass): QueryBuilder
    {
        return $this->hasOne($modelClass);
    }

    /**
     * @ignore
     *
     * @param array<string,mixed>|Row $data *
     *
     * @return ActiveModel
     */
    protected static function returnInstanceOfSelf(Row|array $data = [], ?Connection $connection = null): self
    {
        $class = static::class;

        return new $class($data, $connection);
    }

    protected function onBeforePersist(): bool
    {
        return true;
    }

    protected function onAfterPersist(): bool
    {
        return true;
    }
}
