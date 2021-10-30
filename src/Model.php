<?php

namespace Tomrf\Snek;

use Exception;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $protectedColumns = [];
    protected array $columns = [];

    protected ?Connection $connection = null;
    protected ?Row $row = null;

    protected array $dirty = [];

    public function __construct(?Connection $connection, ?Row $row)
    {
        $this->connection = $connection;
        $this->row = $row;
    }

    private function getAny(string $column): mixed
    {
        $value = $this->dirty[$column] ?? $this->row[$column] ?? null; /* @todo throw exception */
        return $value;

    }

    public function has(string $column): bool
    {
        if (isset($this->dirty[$column]) || isset($this->row[$column])) {
            return true;
        }

        return false;
    }

    public function get(string $column): mixed
    {
        if ($this->isProtected($column)) {
            throw new Exception(sprintf(
                'Access violation getting protected column "%s" for table "%s"',
                $column, $this->table
            ));
        }
        return $this->getAny($column);
    }

    public function getProtected(string $protectedColumn): mixed
    {
        if (!$this->isProtected($protectedColumn)) {
            throw new Exception(sprintf(
                'Getting protected column "%s" for table "%s" but column is not protected or does not exist',
                $protectedColumn, $this->table
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
                $column, $this->table
            ));
        }

        if ($this->isPrimaryKey($column)) {
            throw new Exception(sprintf(
                'Access violation setting primary key column "%s" for table "%s"',
                $column, $this->table
            ));
        }

        $valueType = gettype($value);
        $columnType = $this->columns[$column]['type'] ?? null;

        if ($valueType !== $columnType) {
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
        if ($column !== null) {
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
        if (!$this->isDirty()) {
            return true;
        }

        $params = [];
        $query = sprintf('UPDATE `%s` SET ', $this->table);
        foreach ($this->dirty as $column => $value) {
            $paramName = $column;
            if (isset($params[$paramName])) {
                $paramName = $paramName . substr(md5(random_bytes(32)), 0, 6);
            }
            $query .= sprintf('`%s`=:%s', $column, $paramName);
            if ($column !== \array_key_last($this->dirty)) {
                $query .= ', ';
            }
            $params[$paramName] = $value;
        }

        $query .= sprintf(' WHERE `%s`=:%s LIMIT 1', $this->primaryKey, $this->primaryKey);
        $params[$this->primaryKey] = $this->get($this->primaryKey);

        $statement = $this->connection->getPdo()->prepare($query);
        $statement->execute($params);

        return true;
    }

    public function toArray(): ?array
    {
        $array = [];
        foreach ($this->row as $key => $value) {
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

    // public function __get(mixed $name): mixed
    // {
    //     return $this->get($name);
    // }

    // public function __set(mixed $name, mixed $value): void
    // {
    //     $this->set($name, $value);
    // }

}
