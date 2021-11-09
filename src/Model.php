<?php

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

    protected ?Connection $connection = null;

    public function __construct(Row|array $data = [], ?Connection $connection = null)
    {
        $this->connection = $connection;

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

    public static function fromRow(Row $row, ?Connection $connection = null): self
    {
        $class = get_called_class();

        return new $class($row, $connection);
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

    public function getProtected(string $protectedColumn): mixed
    {
        if (!$this->isProtected($protectedColumn)) {
            throw new Exception(sprintf(
                'Getting protected column "%s" for table "%s" but column is not protected or does not exist',
                $protectedColumn,
                $this->table
            ));
        }

        return $this->getAny($protectedColumn);
    }

    public function getPrimaryKey(): mixed
    {
        return $this->getAny($this->primaryKey);
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

        $valueType = gettype($value);
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

        return count($this->dirty) ? true : false;
    }

    public function isProtected(string $column): bool
    {
        return in_array($column, $this->protectedColumns);
    }

    public function isPrimaryKey(string $column): bool
    {
        return ($column === $this->primaryKey) ? true : false;
    }

    public function save(): bool
    {
        if (null === $this->connection) {
            throw new RuntimeException('Cannot save model: no database connection supplied on model construction');
        }
        if (null === $this->getPrimaryKey()) {
            throw new RuntimeException('Cannot save model: primary key in row data is NULL');
        }

        if (!$this->isDirty()) {
            return true;
        }

        $params = [];
        $query = sprintf('UPDATE `%s` SET ', $this->table);
        foreach ($this->dirty as $column => $value) {
            $paramName = $column;
            if (isset($params[$paramName])) {
                $paramName = $paramName.substr(md5(random_bytes(32)), 0, 6);
            }
            $query .= sprintf('`%s`=:%s', $column, $paramName);
            if ($column !== \array_key_last($this->dirty)) {
                $query .= ', ';
            }
            $params[$paramName] = $value;
        }

        $query .= sprintf(' WHERE `%s`=:%s', $this->primaryKey, $this->primaryKey);
        $params[$this->primaryKey] = $this->getPrimaryKey();

        /** @var PdoConnection */
        $connection = $this->connection;
        $statement = $connection->getPdo()->prepare($query);
        $statement->execute($params);

        return true;
    }

    public function insert(bool $onDuplicateIgnore = false): bool
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
            call_user_func([$this, 'onBeforePersist']);
        }

        // build query
        $params = [];
        $query = sprintf('INSERT INTO `%s` (', $this->table);
        $values = '(';
        foreach ($this->dirty as $column => $value) {
            $query .= sprintf('`%s`', $column);

            // param
            $paramName = $column;
            if (isset($params[$paramName])) {
                $paramName = $paramName.substr(md5(random_bytes(32)), 0, 6);
            }
            $params[$paramName] = $value;
            $values .= sprintf(':%s', $paramName);

            if ($column !== \array_key_last($this->dirty)) {
                $query .= ',';
                $values .= ',';
            } else {
                $values .= ')';
                $query .= ') VALUES '.$values;
            }
        }

        if (true === $onDuplicateIgnore) {
            $query .= ' ON DUPLICATE KEY IGNORE';
        }

        /** @var PdoConnection */
        $connection = $this->connection;
        $statement = $connection->getPdo()->prepare($query);
        $statement->execute($params);

        return true;
    }

    public function toArray(): ?array
    {
        $array = [];
        foreach ($this->data as $key => $value) {
            $array[$key] = $value;
        }
        foreach ($this->dirty as $key => $value) {
            $array[$key] = $value;
        }
        foreach ($this->protectedColumns as $key) {
            unset($array[$key]);
        }

        return $array;
    }

    public function toJson(): string
    {
        return \json_encode($this->toArray());
    }

    protected function getAny(string $column): mixed
    {
        $value = $this->dirty[$column] ?? $this->data[$column] ?? null; // @todo throw exception

        if (null === $value) {
            return null;
        }

        $columnDefinitions = $this->getColumnDefinitions($column);
        if (null !== $columnDefinitions) {
            if (in_array($columnDefinitions->type, ['int', 'integer'])) {
                $value = \intval($value);
            } elseif (in_array($columnDefinitions->type, ['bool', 'boolean'])) {
                $value = \boolval($value);
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
}
