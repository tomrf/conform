<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use DomainException;
use InvalidArgumentException;

class SqlQueryBuilder extends QueryBuilder
{
    /**
     * @var array<int|string,array>
     */
    protected array $select = [];
    /**
     * @var array<int|string,array>
     */
    protected array $join = [];
    /**
     * @var array<int|string,array>
     */
    protected array $where = [];
    /**
     * @var array<int|string,array>
     */
    protected array $order = [];
    /**
     * @var array<int|string,array>
     */
    protected array $set = [];

    protected int $limit = -1;
    protected int $offset = -1;

    protected ?string $onDuplicateKey = null;

    /**
     * @var array<string,mixed>
     */
    protected array $queryParameters = [];

    public function set(string $column, mixed $value): self
    {
        $key = trim($column);

        $this->set[$key] = [
            'value' => $value,
            'raw' => false,
        ];

        return $this;
    }

    public function setRaw(string $column, string $expression): self
    {
        $key = trim($column);

        $this->set[$key] = [
            'value' => $expression,
            'raw' => true,
        ];

        return $this;
    }

    public function onDuplicateKey(string $expression): self
    {
        $this->onDuplicateKey = trim($expression);

        return $this;
    }

    public function select(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->select[] = [
                'expression' => $this->quoteExpression(trim($column)),
            ];
        }

        return $this;
    }

    public function selectAs(string $expression, string $alias): self
    {
        $this->select[] = [
            'expression' => $this->quoteExpression(trim($expression)),
            'alias' => $this->quoteString(trim($alias)),
        ];

        return $this;
    }

    public function selectRaw(string ...$params): self
    {
        foreach ($params as $expression) {
            $this->select[] = [
                'expression' => trim($expression),
            ];
        }

        return $this;
    }

    public function selectRawAs(string $expression, string $alias): self
    {
        $this->select[] = [
            'expression' => trim($expression),
            'alias' => $this->quoteString(trim($alias)),
        ];

        return $this;
    }

    public function alias(string $expression, string $alias): self
    {
        foreach ($this->select as $i => $select) {
            if ($select['expression'] === $expression) {
                $this->select[$i]['alias'] = $this->quoteString(trim($alias));
            }
        }

        return $this;
    }

    public function join(string $table, string $joinCondition): self
    {
        $this->join[] = [
            'table' => trim($table),
            'condition' => trim($joinCondition),
        ];

        return $this;
    }

    public function whereRaw(string $expression): self
    {
        $key = (string) crc32($expression);
        $this->where[$key] = [
            'condition' => $expression,
        ];

        return $this;
    }

    public function whereColumnRaw(string $column, string $expression): self
    {
        return $this->whereRaw(
            sprintf(
                '%s %s',
                $this->quoteExpression(trim($column)),
                $expression
            )
        );
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $key = trim($column);
        $this->where[$key] = [
            'value' => $value,
            'condition' => sprintf(
                '%s %s :%s',
                $this->quoteExpression(trim($column)),
                trim($operator),
                trim($column)
            ),
        ];

        return $this;
    }

    public function whereEqual(string $column, mixed $value): self
    {
        return $this->where($column, '=', $value);
    }

    public function whereNotEqual(string $column, mixed $value): self
    {
        return $this->where($column, '!=', $value);
    }

    public function whereNull(string $column): self
    {
        return $this->whereColumnRaw($column, 'IS NULL');
    }

    public function whereNotNull(string $column): self
    {
        return $this->whereColumnRaw($column, 'IS NOT NULL');
    }

    public function orderByAsc(string $column): self
    {
        $this->order[] = [
            'column' => trim($column),
            'direction' => 'ASC',
        ];

        return $this;
    }

    public function orderByDesc(string $column): self
    {
        $this->order[] = [
            'column' => trim($column),
            'direction' => 'DESC',
        ];

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
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

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Negative offset not allowed');
        }

        $this->offset = $offset;

        return $this;
    }

    public function getQuery(): string
    {
        return $this->buildQuery();
    }

    /**
     * @return array<string,mixed>
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getQueryAndParameters(): mixed
    {
        return [$this->buildQuery(), $this->queryParameters];
    }

    protected function buildQuerySelectExpression(): string
    {
        if (0 === \count($this->select)) {
            return sprintf('%s.*', $this->quoteExpression($this->table));
        }

        $selectExpression = '';

        foreach ($this->select as $key => $select) {
            $selectExpression .= sprintf(
                '%s%s',
                $select['expression'],
                isset($select['alias']) ? (' AS '.$select['alias']) : ''
            );

            if ($key !== array_key_last($this->select)) {
                $selectExpression .= ',';
            }
        }

        return $selectExpression;
    }

    protected function buildQueryJoinClause(): string
    {
        $joinClause = '';
        foreach ($this->join as $join) {
            $joinClause .= sprintf(
                ' JOIN %s ON %s',
                $this->quoteExpression($join['table']),
                $this->quoteExpression($join['condition'])
            );
        }

        return $joinClause;
    }

    protected function buildQueryInsertStatement(): string
    {
        $columns = '';
        $values = '';

        if (0 === \count($this->set)) {
            return '';
        }

        foreach ($this->set as $column => $valueData) {
            $isRaw = $valueData['raw'];
            $value = $valueData['value'];

            $column = (string) $column;

            $columns .= sprintf('%s, ', $this->quoteExpression($column));

            if (true === $isRaw) {
                $values .= sprintf('%s, ', $value);
            } else {
                $values .= sprintf(':%s, ', $column);
                $this->queryParameters[$column] = $value;
            }
        }

        return sprintf(
            '(%s) VALUES (%s)',
            trim($columns, ', '),
            trim($values, ', ')
        );
    }

    protected function buildQuerySetStatement(): string
    {
        if (0 === \count($this->set)) {
            return '';
        }

        $statement = '';

        foreach ($this->set as $column => $assignment) {
            $isRaw = $assignment['raw'];
            $value = $assignment['value'];

            if (true === $isRaw) {
                $statement .= sprintf(
                    '%s = %s',
                    $this->quoteExpression((string) $column),
                    $value
                );
            } else {
                $statement .= sprintf(
                    '%s = :%s',
                    $this->quoteExpression((string) $column),
                    $column
                );

                $this->queryParameters[(string) $column] = $value;
            }

            if ($column !== array_key_last($this->set)) {
                $statement .= ', ';
            }
        }

        return $statement;
    }

    protected function buildQueryWhereCondition(): string
    {
        if (0 === \count($this->where)) {
            return '';
        }

        $whereCondition = ' WHERE ';

        foreach ($this->where as $key => $where) {
            $whereCondition .= $where['condition'];

            if (array_key_last($this->where) !== $key) {
                $whereCondition .= ' AND ';
            }

            if (isset($where['value'])) {
                $this->queryParameters[(string) $key] = $where['value'];
            }
        }

        return $whereCondition;
    }

    protected function buildQueryOrderByClause(): string
    {
        if (0 === \count($this->order)) {
            return '';
        }

        $orderByClause = ' ORDER BY ';
        foreach ($this->order as $key => $orderBy) {
            $orderByClause .= sprintf(
                '%s %s%s',
                $this->quoteExpression($orderBy['column']),
                $orderBy['direction'],
                ($key !== array_key_last($this->order)) ? ', ' : ''
            );
        }

        return $orderByClause;
    }

    protected function buildQuery(): string
    {
        // INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
        //     [INTO] tbl_name
        //     SET assignment_list
        //     [ON DUPLICATE KEY UPDATE assignment_list]

        if (str_starts_with($this->statement, 'INSERT')) {
            return trim(sprintf(
                'INSERT INTO %s %s %s',
                $this->quoteExpression($this->table),
                $this->buildQueryInsertStatement(),
                $this->onDuplicateKey ? 'ON DUPLICATE KEY '.$this->onDuplicateKey : ''
            ));
        }

        // UPDATE [LOW_PRIORITY] [IGNORE] table_reference
        //     SET assignment_list
        //     [WHERE where_condition]
        //     [ORDER BY ...]
        //     [LIMIT row_count]

        if (str_starts_with($this->statement, 'UPDATE')) {
            return trim(sprintf(
                'UPDATE %s SET %s%s%s%s%s',
                $this->quoteExpression($this->table),
                $this->buildQuerySetStatement(),
                $this->buildQueryWhereCondition(),
                $this->buildQueryOrderByClause(),
                (-1 !== $this->limit) ? sprintf(' LIMIT %d', $this->limit) : '',
                (-1 !== $this->offset) ? sprintf(' OFFSET %d', $this->offset) : ''
            ));
        }

        // DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name [[AS] tbl_alias]
        //     [WHERE where_condition]
        //     [ORDER BY ...]
        //     [LIMIT row_count]

        if (str_starts_with($this->statement, 'DELETE')) {
            return trim(sprintf(
                'DELETE FROM %s%s%s%s%s',
                $this->quoteExpression($this->table),
                $this->buildQueryWhereCondition(),
                $this->buildQueryOrderByClause(),
                (-1 !== $this->limit) ? sprintf(' LIMIT %d', $this->limit) : '',
                (-1 !== $this->offset) ? sprintf(' OFFSET %d', $this->offset) : ''
            ));
        }

        return sprintf(
            'SELECT %s FROM %s%s%s%s%s%s',
            $this->buildQuerySelectExpression(),
            $this->quoteExpression($this->table),
            $this->buildQueryJoinClause(),
            $this->buildQueryWhereCondition(),
            $this->buildQueryOrderByClause(),
            (-1 !== $this->limit) ? sprintf(' LIMIT %d', $this->limit) : '',
            (-1 !== $this->offset) ? sprintf(' OFFSET %d', $this->offset) : ''
        );
    }

    protected function assertQueryState(): void
    {
        if (-1 !== $this->offset && -1 === $this->limit) {
            throw new DomainException(
                'Invalid query: offset specified without a limit clause'
            );
        }
    }

    protected function quoteString(string $string): string
    {
        return sprintf('"%s"', $string);
    }

    protected function quoteExpression(string $expression): string
    {
        $quotedExpression = '';

        if (mb_strstr($expression, ' ')) {
            $parts = explode(' ', $expression);

            foreach ($parts as $part) {
                $quotedExpression .= $this->quoteExpression($part);
            }

            return $quotedExpression;
        }

        if (mb_strstr($expression, '.')) {
            $parts = explode('.', $expression);

            foreach ($parts as $key => $part) {
                $quotedExpression .= $this->quoteExpression($part);
                if ($key !== array_key_last($parts)) {
                    $quotedExpression .= '.';
                }
            }

            return $quotedExpression;
        }

        if (!$this->isValidColumnName($expression)) {
            return $expression;
        }
        if ('*' === $expression) {
            return $expression;
        }

        return sprintf('`%s`', $expression);
    }

    protected function isQuotedExpression(string $expression): bool
    {
        $offsetEnd = -1 + mb_strlen($expression);
        if ('`' === $expression[0] && '`' === $expression[$offsetEnd]) {
            return true;
        }

        return false;
    }

    protected function isValidColumnName(string $name): bool
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            return false;
        }

        return true;
    }
}
