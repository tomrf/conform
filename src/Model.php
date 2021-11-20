<?php

declare(strict_types=1);

namespace Tomrf\Snek;

use Exception;
use RuntimeException;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $protectedColumns = [];
    protected array $columns = [];

    protected array $data = [];
    protected array $dirty = [];

    public function __construct(Row|array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function __get(mixed $name): mixed
    {
        throw new Exception('Access violation directly getting arbitrary property from Model');
    }

    public function __set(mixed $name, mixed $value): void
    {
        throw new Exception('Access violation directly setting arbitrary property on Model');
    }

    public static function fromRow(Row $row): self
    {
        return self::returnInstanceOfSelf($row);
    }

    public static function fromObject(self $modelObject): self
    {
        return self::returnInstanceOfSelf($modelObject->toArray());
    }

    public static function byPrimaryKey(Connection $connection, int|string $id): self
    {
        return self::byColumn($connection, $id, self::getPrimaryKeyName());
    }

    public static function byColumn(Connection $connection, int|string $id, string $column): self
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

        return self::fromRow($row);
    }

    public function has(string $column): bool
    {
        if (isset($this->dirty[$column]) || isset($this->data[$column])) {
            return true;
        }

        return false;
    }

    public function get(string $column): mixed
    {
        if ($this->isProtected($column)) {
            throw new Exception(sprintf(
                'Access violation getting protected column "%s" for table "%s"',
                $column,
                $this->table
            ));
        }

        return $this->getAny($column);
    }

    public function getDirty(): array
    {
        return $this->dirty;
    }

    public function getProtected(string $protectedColumn): mixed
    {
        if (!$this->isProtected($protectedColumn)) {
            throw new Exception(sprintf(
                'Protected column "%s" for table "%s" is not protected or does not exist',
                $protectedColumn,
                $this->table
            ));
        }

        return $this->getAny($protectedColumn);
    }

    public function getPrimaryKey(): mixed
    {
        return $this->getAny($this->getPrimaryKeyColumn());
    }

    public function getPrimaryKeyColumn(): string
    {
        return $this->primaryKey;
    }

    public function set(string $column, mixed $value): mixed
    {
        if ($this->isProtected($column)) {
            throw new Exception(sprintf(
                'Access violation setting protected column "%s" for table "%s"',
                $column,
                $this->table
            ));
        }

        if ($this->isPrimaryKey($column)) {
            throw new Exception(sprintf(
                'Access violation setting primary key column "%s" for table "%s"',
                $column,
                $this->table
            ));
        }

        $valueType = \gettype($value);
        $columnType = $this->columns[$column]['type'] ?? null;

        if (null !== $columnType && $valueType !== $columnType) {
            throw new Exception(sprintf(
                'Illegal type "%s" (expected "%s") for column "%s" in table "%s"',
                $valueType,
                $columnType,
                $column,
                $this->table
            ));
        }

        return $this->dirty[$column] = $value;
    }

    public function isDirty(string $column = null): bool
    {
        if (null !== $column) {
            return isset($this->dirty[$column]) ? true : false;
        }

        return \count($this->dirty) ? true : false;
    }

    public function isProtected(string $column): bool
    {
        return \in_array($column, $this->protectedColumns, true);
    }

    public function isPrimaryKey(string $column): bool
    {
        return ($column === $this->primaryKey) ? true : false;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /** @todo fix move to connection */
    public function insert(bool $insertIgnore = false): bool
    {
        if (null === $this->connection) {
            throw new RuntimeException('Cannot save model: no database connection supplied on model construction');
        }

        // set defaults
        foreach ($this->columns as $column => $attributes) {
            if (isset($attributes['default']) && !$this->has($column)) {
                $this->dirty[$column] = $attributes['default'];
            }
        }

        if (method_exists($this, 'onBeforePersist')) {
            \call_user_func([$this, 'onBeforePersist']);
        }

        // build query
        $params = [];
        $query = sprintf('INSERT INTO `%s` (', $this->table);
        if (true === $insertIgnore) {
            $query = sprintf('INSERT IGNORE INTO `%s` (', $this->table);
        }
        $values = '(';
        foreach ($this->dirty as $column => $value) {
            $query .= sprintf('`%s`', $column);

            // param
            $paramName = $column;
            if (isset($params[$paramName])) {
                $paramName = $paramName.mb_substr(md5(random_bytes(32)), 0, 6);
            }
            $params[$paramName] = $value;
            $values .= sprintf(':%s', $paramName);

            if ($column !== array_key_last($this->dirty)) {
                $query .= ',';
                $values .= ',';
            } else {
                $values .= ')';
                $query .= ') VALUES '.$values;
            }
        }

        /** @var PdoConnection */
        $connection = $this->connection;
        $statement = $connection->getPdo()->prepare($query);
        $statement->execute($params);

        return true;
    }

    public function toArray(bool $includeProtectedColumns = false): ?array
    {
        $array = [];

        // first set initial values
        foreach ($this->data as $key => $value) {
            $array[$key] = $this->getAny($key);
        }

        // then overwrite dirty keys
        foreach ($this->dirty as $key => $value) {
            $array[$key] = $this->getAny($key);
        }

        // finally remove protected keys
        if (false === $includeProtectedColumns) {
            foreach ($this->protectedColumns as $key) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function toJson(bool $includeProtectedColumns = false): string
    {
        return json_encode($this->toArray($includeProtectedColumns));
    }

    public function persist(Connection $connection): bool
    {
        if (false === $this->onBeforePersist()) {
            // @todo throw ?
            return false;
        }

        $connection->persist($this);
        $this->flushDirty();

        return $this->onAfterPersist();
    }

    protected static function getPrimaryKeyName(): string
    {
        $class = static::class;
        $modelInstance = new $class();
        $primaryKeyColumn = $modelInstance->primaryKey;
        unset($modelInstance);

        return $primaryKeyColumn;
    }

    protected function flushDirty(): void
    {
        foreach ($this->dirty as $key => $value) {
            $this->data[$key] = $value;
        }

        $this->dirty = [];
    }

    protected function getAny(string $column): mixed
    {
        $value = $this->dirty[$column] ?? $this->data[$column] ?? null; // @todo throw exception

        if (null === $value) {
            return null;
        }

        $columnDefinitions = $this->getColumnDefinitions($column);
        if (null !== $columnDefinitions) {
            if (\in_array($columnDefinitions->type, ['int', 'integer'], true)) {
                $value = (int) $value;
            } elseif (\in_array($columnDefinitions->type, ['bool', 'boolean'], true)) {
                $value = (bool) $value;
            }
        }

        return $value;
    }

    protected function getColumnDefinitions(string $column): ?object
    {
        $definition = $this->columns[$column] ?? null;

        if (null === $definition) {
            return null;
        }

        return (object) $definition;
    }

    protected static function returnInstanceOfSelf(Row|array $data = []): self
    {
        $class = static::class;

        return new $class($data);
    }

    protected function onBeforePersist(): bool
    {
        return true;
    }

    protected function onAfterPersist(): bool
    {
        return true;
    }

    private static function getTableName(): string
    {
        $class = static::class;
        $modelInstance = new $class();
        $tableName = $modelInstance->table;
        unset($modelInstance);

        return $tableName;
    }
}
