<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use InvalidArgumentException;
use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Sql\SqlQueryCompiler;

class QueryBuilder extends SqlQueryCompiler implements QueryBuilderInterface
{
    protected string $table = '';
    protected string $statement = '';

    public function __toString(): string
    {
        return $this->getQuery();
    }

    public function selectFrom(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('SELECT');

        return $this;
    }

    public function insertInto(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('INSERT INTO');

        return $this;
    }

    public function update(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('UPDATE');

        return $this;
    }

    public function deleteFrom(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('DELETE FROM');

        return $this;
    }

    public function set(string $column, mixed $value): static
    {
        $key = trim($column);

        $this->set[$key] = [
            'value' => $value,
            'raw' => false,
        ];

        return $this;
    }

    public function setRaw(string $column, string $expression): static
    {
        $key = trim($column);

        $this->set[$key] = [
            'value' => $expression,
            'raw' => true,
        ];

        return $this;
    }

    public function select(string ...$columns): static
    {
        foreach ($columns as $column) {
            $this->select[] = [
                'expression' => $this->quoteExpression(trim($column)),
            ];
        }

        return $this;
    }

    public function selectAs(string $expression, string $alias): static
    {
        $this->select[] = [
            'expression' => $this->quoteExpression(trim($expression)),
            'alias' => $this->quoteString(trim($alias)),
        ];

        return $this;
    }

    public function selectRaw(string ...$params): static
    {
        foreach ($params as $expression) {
            $this->select[] = [
                'expression' => trim($expression),
            ];
        }

        return $this;
    }

    public function selectRawAs(string $expression, string $alias): static
    {
        $this->select[] = [
            'expression' => trim($expression),
            'alias' => $this->quoteString(trim($alias)),
        ];

        return $this;
    }

    public function alias(string $expression, string $alias): static
    {
        foreach ($this->select as $i => $select) {
            if ($select['expression'] === $expression) {
                $this->select[$i]['alias'] = $this->quoteString(trim($alias));
            }
        }

        return $this;
    }

    public function join(string $table, string $joinCondition): static
    {
        $this->join[] = [
            'table' => trim($table),
            'condition' => trim($joinCondition),
        ];

        return $this;
    }

    public function whereRaw(string $expression): static
    {
        $key = (string) crc32($expression);
        $this->where[$key] = [
            'condition' => $expression,
        ];

        return $this;
    }

    public function whereColumnRaw(string $column, string $expression): static
    {
        return $this->whereRaw(
            sprintf(
                '%s %s',
                $this->quoteExpression(trim($column)),
                $expression
            )
        );
    }

    public function where(string $column, string $operator, mixed $value): static
    {
        $this->where[] = [
            'value' => $value,
            'condition' => sprintf(
                '%s %s ?',
                $this->quoteExpression(trim($column)),
                trim($operator),
            ),
        ];

        return $this;
    }

    public function whereEqual(string $column, mixed $value): static
    {
        return $this->where($column, '=', $value);
    }

    public function whereNotEqual(string $column, mixed $value): static
    {
        return $this->where($column, '!=', $value);
    }

    public function whereNull(string $column): static
    {
        return $this->whereColumnRaw($column, 'IS NULL');
    }

    public function whereNotNull(string $column): static
    {
        return $this->whereColumnRaw($column, 'IS NOT NULL');
    }

    public function orderByAsc(string $column): static
    {
        $this->order[] = [
            'column' => trim($column),
            'direction' => 'ASC',
        ];

        return $this;
    }

    public function orderByDesc(string $column): static
    {
        $this->order[] = [
            'column' => trim($column),
            'direction' => 'DESC',
        ];

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): static
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Negative limit not allowed');
        }

        $this->limit = $limit;

        if (null !== $offset) {
            return $this->offset($offset);
        }

        return $this;
    }

    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Negative offset not allowed');
        }

        $this->offset = $offset;

        return $this;
    }

    public function onDuplicateKey(string $expression): static
    {
        $this->onDuplicateKey = trim($expression);

        return $this;
    }

    protected function setTable(string $table): void
    {
        $this->table = $table;
    }

    protected function setStatement(string $type): void
    {
        $this->statement = $type;
    }
}
