<?php

namespace Tomrf\Snek;

use Exception;

class QueryBuilder
{
    /* table */
    private string $table = '';

    /* query parts */
    private array $select = [];
    private array $join = [];
    private array $where = [];
    private array $orderBy = [];

    private int $limit = -1;
    private int $offset = -1;

    /* query parameters */
    private array $queryParameters = [];

    public function __construct(
        private QueryExecuter $queryExecuter,
    ) {}

    public function forTable(string $table): QueryBuilder
    {
        $this->table = $table;
        return $this;
    }

    public function select(string $name, string $alias = null): QueryBuilder
    {
        $this->select[] = [
            'expression' => trim($name),
            'alias' => trim($alias),
        ];
        return $this;
    }

    public function join(string $table, string $joinCondition): QueryBuilder
    {
        $this->join[] = [
            'table' => trim($table),
            'condition' => trim($joinCondition),
        ];
        return $this;
    }

    public function where(string $key, mixed $value): QueryBuilder
    {
        $this->where[] = [
            'left' => trim($key),
            'right' => is_string($value) ? trim($value) : $value,
            'operator' => '='
        ];
        return $this;
    }

    public function orderByAsc(string $column): QueryBuilder
    {
        $this->orderBy[] = [
            'column' => trim($column),
            'direction' => 'ASC'
        ];
        return $this;
    }


    public function orderByDesc(string $column): QueryBuilder
    {
        $this->orderBy[] = [
            'column' => trim($column),
            'direction' => 'DESC'
        ];
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): QueryBuilder
    {
        if ($limit < 0) {
            throw new \Exception('Illegal (negative) LIMIT value specified');
        }

        $this->limit = $limit;

        if ($offset !== null) {
            return $this->offset($offset);
        }

        return $this;
    }

    public function offset(int $offset, ?int $limit = null): QueryBuilder
    {
        if ($offset < 0) {
            throw new \Exception('Illegal (negative) OFFSET value specified');

        }
        $this->offset = $offset;

        if ($limit !== null) {
            return $this->limit($limit);
        }

        return $this;
    }

    private function buildQuerySelectExpression(): string
    {
        if (count($this->select) === 0) {
            return '*';
        }

        $selectExpression = '';

        foreach ($this->select as $key => $select) {
            $selectExpression .= sprintf('%s%s',
                $this->quoteExpression($select['expression']),
                $select['alias'] ? (' AS ' . $this->quoteString($select['alias'])) : ''
            );

            if ($key !== \array_key_last($this->select)) {
                $selectExpression .= ',';
            }
        }

        return $selectExpression;
    }

    private function buildQueryJoinClause(): string
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

    private function buildQueryWhereCondition(): string
    {
        if (count($this->where) === 0) {
            return '';
        }

        $whereCondition = ' WHERE ';
        foreach ($this->where as $key => $where) {
            if (isset($this->queryParameters[$where['left']])) {
                throw new Exception(sprintf(
                    'Duplicate parameter "%s" in WHERE condition for table "%s"',
                    $where['left'], $this->table
                ));
            }

            $parameterName = str_replace('.', '_', $where['left']);
            $this->queryParameters[$parameterName] = $where['right'];

            $whereCondition .= sprintf('%s%s:%s%s',
                $this->quoteExpression($where['left']),
                $where['operator'],
                $parameterName,
                ($key !== \array_key_last($this->where)) ? ' AND ' : ''
            );
        }

        return $whereCondition;
    }

    private function buildQueryOrderByClause(): string
    {
        if (count($this->orderBy) === 0) {
            return '';
        }

        $orderByClause = ' ORDER BY ';
        foreach ($this->orderBy as $key => $orderBy) {
            $orderByClause .= sprintf('%s %s%s',
                $this->quoteExpression($orderBy['column']),
                $orderBy['direction'],
                ($key !== \array_key_last($this->orderBy)) ? ', ' : ''
            );
        }

        return $orderByClause;
    }

    private function buildQuery(): string
    {
        return sprintf(
            'SELECT %s FROM %s%s%s%s%s%s',
            $this->buildQuerySelectExpression(),
            $this->quoteExpression($this->table),
            $this->buildQueryJoinClause(),
            $this->buildQueryWhereCondition(),
            $this->buildQueryOrderByClause(),
            ($this->limit !== -1) ? sprintf(' LIMIT %d', $this->limit) : '',
            ($this->offset !== -1) ? sprintf(' OFFSET %d', $this->offset) : ''
        );
    }

    private function assertQueryState(): void
    {
        if ($this->offset !== -1 && $this->limit === -1) {
            throw new \Exception('Query validation failed: OFFSET specified without LIMIT clause');
        }
    }

    public function findOne(): Row|bool
    {
        $this->assertQueryState();

        return $this->queryExecuter->findOne(
            $this->buildQuery(),
            $this->queryParameters
        );
    }

    public function findMany(): ?array
    {
        $this->assertQueryState();

        return $this->queryExecuter->findMany(
            $this->buildQuery(),
            $this->queryParameters
        );
    }

    private function quoteString(string $string): string
    {
        return sprintf('"%s"', $string);
    }

    private function quoteExpression(string $expression): string
    {
        $quotedExpression = '';

        if (\mb_strstr($expression, ' ')) {
            $parts = \explode(' ', $expression);

            foreach ($parts as $part) {
                $quotedExpression .= $this->quoteExpression($part);
            }

            return $quotedExpression;
        }

        if (\mb_strstr($expression, '.')) {
            $parts = \explode('.', $expression);

            foreach ($parts as $key => $part) {
                $quotedExpression .= $this->quoteExpression($part);
                if ($key !== \array_key_last($parts)) {
                    $quotedExpression .= '.';
                }
            }

            return $quotedExpression;
        }

        if (!$this->isValidColumnName($expression)) {
            return $expression;
        } elseif ($expression === '*') {
            return $expression;
        }

        return sprintf('`%s`', $expression);
    }

    private function isExpressionQuoted(string $expression): bool
    {
        $offsetEnd = -1 + \mb_strlen($expression);
        if ($expression[0] === '`' && $expression[$offsetEnd] === '`') {
            return true;
        }
        return false;
    }

    private function isValidColumnName(string $name): bool
    {
        if (!\preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            return false;
        }

        return true;
    }

}
