<?php

namespace Tomrf\Snek;

use Exception;

class QueryBuilder
{
    // table
    protected string $table = '';

    // query parts
    protected array $select = [];
    protected array $join = [];
    protected array $where = [];
    protected array $orderBy = [];

    protected int $limit = -1;
    protected int $offset = -1;

    // query parameters
    protected array $queryParameters = [];

    public function __construct(
        protected QueryExecuter $queryExecuter,
    ) {
    }

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
            'operator' => '=',
        ];

        return $this;
    }

    public function orderByAsc(string $column): QueryBuilder
    {
        $this->orderBy[] = [
            'column' => trim($column),
            'direction' => 'ASC',
        ];

        return $this;
    }

    public function orderByDesc(string $column): QueryBuilder
    {
        $this->orderBy[] = [
            'column' => trim($column),
            'direction' => 'DESC',
        ];

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): QueryBuilder
    {
        if ($limit < 0) {
            throw new \Exception('Illegal (negative) LIMIT value specified');
        }

        $this->limit = $limit;

        if (null !== $offset) {
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

        if (null !== $limit) {
            return $this->limit($limit);
        }

        return $this;
    }

    public function findOne(): Row|bool
    {
        $this->limit = 1;
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
            $this->getQueryParameters()
        );
    }

    protected function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    protected function buildQuerySelectExpression(): string
    {
        if (0 === count($this->select)) {
            return '*';
        }

        $selectExpression = '';

        foreach ($this->select as $key => $select) {
            $selectExpression .= sprintf(
                '%s%s',
                $this->quoteExpression($select['expression']),
                $select['alias'] ? (' AS '.$this->quoteString($select['alias'])) : ''
            );

            if ($key !== \array_key_last($this->select)) {
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

    protected function buildQueryWhereCondition(): string
    {
        if (0 === count($this->where)) {
            return '';
        }

        $whereCondition = ' WHERE ';
        foreach ($this->where as $key => $where) {
            if (isset($this->queryParameters[$where['left']])) {
                throw new Exception(sprintf(
                    'Duplicate parameter "%s" in WHERE condition for table "%s"',
                    $where['left'],
                    $this->table
                ));
            }

            $parameterName = str_replace('.', '_', $where['left']);
            $this->queryParameters[$parameterName] = $where['right'];

            $whereCondition .= sprintf(
                '%s%s:%s%s',
                $this->quoteExpression($where['left']),
                $where['operator'],
                $parameterName,
                ($key !== \array_key_last($this->where)) ? ' AND ' : ''
            );
        }

        return $whereCondition;
    }

    protected function buildQueryOrderByClause(): string
    {
        if (0 === count($this->orderBy)) {
            return '';
        }

        $orderByClause = ' ORDER BY ';
        foreach ($this->orderBy as $key => $orderBy) {
            $orderByClause .= sprintf(
                '%s %s%s',
                $this->quoteExpression($orderBy['column']),
                $orderBy['direction'],
                ($key !== \array_key_last($this->orderBy)) ? ', ' : ''
            );
        }

        return $orderByClause;
    }

    protected function buildQuery(): string
    {
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
            throw new \Exception('Query validation failed: OFFSET specified without LIMIT clause');
        }
    }

    protected function quoteString(string $string): string
    {
        return sprintf('"%s"', $string);
    }

    protected function quoteExpression(string $expression): string
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
        }
        if ('*' === $expression) {
            return $expression;
        }

        return sprintf('`%s`', $expression);
    }

    protected function isExpressionQuoted(string $expression): bool
    {
        $offsetEnd = -1 + \mb_strlen($expression);
        if ('`' === $expression[0] && '`' === $expression[$offsetEnd]) {
            return true;
        }

        return false;
    }

    protected function isValidColumnName(string $name): bool
    {
        if (!\preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            return false;
        }

        return true;
    }
}
