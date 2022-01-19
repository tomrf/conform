<?php

declare(strict_types=1);

namespace Tomrf\Conform\ActiveRecord;

use Exception;
use Tomrf\Conform\Row;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    /**
     * @var array<string>
     */
    protected array $protectedColumns = [];

    /**
     * @var array<string, array>
     */
    protected array $columns = [];

    /**
     * @ignore
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @ignore
     *
     * @var array<string, mixed>
     */
    protected array $dirty = [];

    /**
     * @ignore
     *
     * @param array<string,mixed>|Row $data
     */
    public function __construct(Row|array $data = [])
    {
        $this->setDefaults();

        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    /**
     * @ignore
     */
    public function __get(mixed $name): mixed
    {
        throw new Exception('Access violation directly getting arbitrary property from Model: '.$name);
    }

    /**
     * @ignore
     */
    public function __set(mixed $name, mixed $value): void
    {
        throw new Exception('Access violation directly setting arbitrary property on Model: '.$name);
    }

    public static function new(): self
    {
        return self::returnInstanceOfSelf();
    }

    public static function fromRow(Row $row): self
    {
        return self::returnInstanceOfSelf($row);
    }

    public static function fromObject(self|Model $modelObject): self
    {
        return self::returnInstanceOfSelf($modelObject->toArray());
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

    /**
     * @return array<string,mixed>
     */
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

        return $this->setAny($column, $value);
    }

    public function setProtected(string $column, mixed $value): mixed
    {
        if (!$this->isProtected($column)) {
            throw new Exception(sprintf(
                'Protected column "%s" for table "%s" is not protected or does not exist',
                $column,
                $this->table
            ));
        }

        return $this->setAny($column, $value);
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

    /**
     * @return null|array<string,mixed>
     */
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
        return json_encode($this->toArray($includeProtectedColumns), JSON_THROW_ON_ERROR);
    }

    protected function setAny(string $column, mixed $value): mixed
    {
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

    protected function setDefaults(): void
    {
        foreach ($this->columns as $column => $attributes) {
            if (isset($attributes['default'])) {
                $this->data[$column] = $attributes['default'];
            }
        }
    }

    /**
     * @ignore
     */
    protected static function getPrimaryKeyName(): string
    {
        $class = static::class;
        $modelInstance = new $class();
        $primaryKeyColumn = $modelInstance->primaryKey;
        unset($modelInstance);

        return $primaryKeyColumn;
    }

    /**
     * @ignore
     */
    protected function flushDirty(): void
    {
        foreach ($this->dirty as $key => $value) {
            $this->data[$key] = $value;
        }

        $this->dirty = [];
    }

    /**
     * @ignore
     */
    protected function getAny(string $column): mixed
    {
        $value = $this->dirty[$column] ?? $this->data[$column] ?? null; // @todo throw exception

        if (null === $value) {
            return null;
        }

        $columnDefinitions = $this->getColumnDefinitions($column);
        if (null !== $columnDefinitions) {
            $type = $columnDefinitions->type;
            if (null !== $type) {
                if (\in_array($type, ['int', 'integer'], true)) {
                    $value = (int) $value;
                } elseif (\in_array($type, ['bool', 'boolean'], true)) {
                    $value = (bool) $value;
                }
            }
        }

        return $value;
    }

    /**
     * @ignore
     */
    protected function getColumnDefinitions(string $column): ?object
    {
        $definition = $this->columns[$column] ?? null;

        return null === $definition ? null : (object) $definition;
    }

    /**
     * @ignore
     *
     * @param array<string,mixed>|Row $data *
     *
     * @return Model
     */
    protected static function returnInstanceOfSelf(Row|array $data = []): self
    {
        $class = static::class;

        return new $class($data);
    }

    /**
     * @ignore
     */
    protected static function getTableName(): string
    {
        $class = static::class;
        $modelInstance = new $class();
        $tableName = $modelInstance->table;
        unset($modelInstance);

        return $tableName;
    }
}
