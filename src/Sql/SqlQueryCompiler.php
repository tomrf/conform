<?php

declare(strict_types=1);

namespace Tomrf\Conform\Sql;

use DomainException;

class SqlQueryCompiler
{
    protected string $table;
    protected string $statement;

    protected int $limit = -1;
    protected int $offset = -1;

    protected ?string $onDuplicateKey = null;

    /**
     * @var array<int|string,mixed>
     */
    protected array $select = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $join = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $where = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $order = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $set = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $queryParameters = [];

    public function getQuery(): string
    {
        $this->queryParameters = [];

        return $this->compileQuery();
    }

    /**
     * @return array<int|string,mixed>
     */
    public function getQueryParameters(): array
    {
        if (0 === \count($this->queryParameters)) {
            $this->compileQuery();
        }

        return $this->queryParameters;
    }

    protected function _select(): string
    {
        if (0 === \count($this->select)) {
            return sprintf('%s.*', $this->quoteExpression($this->table));
        }

        foreach ($this->select as $key => $select) {
            $selectExpression = sprintf(
                '%s%s%s',
                $selectExpression ?? '',
                $select['expression'],
                isset($select['alias']) ? (' AS '.$select['alias']) : ''
            );

            if ($key !== array_key_last($this->select)) {
                $selectExpression .= ', ';
            }
        }

        return $selectExpression;
    }

    protected function _join(): string
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

    protected function _insert(): string
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
                $values .= '?, ';
                $this->queryParameters[] = $value;
            }
        }

        return sprintf(
            '(%s) VALUES (%s)',
            trim($columns, ', '),
            trim($values, ', ')
        );
    }

    protected function _set(): string
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
                    '%s = ?',
                    $this->quoteExpression((string) $column),
                );

                $this->queryParameters[] = $value;
            }

            if ($column !== array_key_last($this->set)) {
                $statement .= ', ';
            }
        }

        return $statement;
    }

    protected function _where(): string
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
                $this->queryParameters[] = $where['value'];
            }
        }

        return $whereCondition;
    }

    protected function _orderBy(): string
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

    protected function compileQuery(): string
    {
        // INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
        //     [INTO] tbl_name
        //     SET assignment_list
        //     [ON DUPLICATE KEY UPDATE assignment_list]

        if (str_starts_with($this->statement, 'INSERT')) {
            return trim(sprintf(
                'INSERT INTO %s %s %s',
                $this->quoteExpression($this->table),
                $this->_insert(),
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
                $this->_set(),
                $this->_where(),
                $this->_orderBy(),
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
                $this->_where(),
                $this->_orderBy(),
                (-1 !== $this->limit) ? sprintf(' LIMIT %d', $this->limit) : '',
                (-1 !== $this->offset) ? sprintf(' OFFSET %d', $this->offset) : ''
            ));
        }

        return sprintf(
            'SELECT %s FROM %s%s%s%s%s%s',
            $this->_select(),
            $this->quoteExpression($this->table),
            $this->_join(),
            $this->_where(),
            $this->_orderBy(),
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
