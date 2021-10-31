<?php

namespace Tomrf\Snek;

use Exception;
use PDO;
use PDOStatement;

class Query
{
    /* query parts */
    private array $select = [];
    private array $join = [];
    private array $where = [];
    private array $orderBy = [];

    private int $limit = -1;
    private int $offset = -1;

    /* query parameters */
    private array $queryParameters = [];

    /* model */
    // private ModelFactory $modelFactory;


    public function __construct(
        private Connection $connection,
        private ?string $table = null,
        private ?ModelFactory $modelFactory = null
    ) {}

    public function select(string $name, string $alias = null): Query
    {
        $this->select[] = [
            'expression' => trim($name),
            'alias' => trim($alias),
        ];

        return $this;
    }

    public function join(string $table, string $joinCondition): Query
    {
        $this->join[] = [
            'table' => trim($table),
            'condition' => trim($joinCondition),
        ];
        return $this;
    }

    public function where(string $key, mixed $value): Query
    {
        $this->where[] = [
            'left' => trim($key),
            'right' => is_string($value) ? trim($value) : $value,
            'operator' => '='
        ];
        return $this;
    }

    public function orderByAsc($column): Query
    {
        $this->orderBy[] = [
            'column' => trim($column),
            'direction' => 'ASC'
        ];

        return $this;
    }


    public function orderByDesc($column): Query
    {
        $this->orderBy[] = [
            'column' => trim($column),
            'direction' => 'DESC'
        ];

        return $this;

    }

    public function limit(int $limit, ?int $offset = null): Query
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

    public function offset(int $offset, ?int $limit = null): Query
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

    public function setTable(string $table): Query
    {
        $this->table = $table;
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

    private function executeQuery(string $query): PDOStatement
    {
        \var_dump($query);

        $this->assertQueryState();

        try {
            $statement = $this->connection->getPdo()->prepare(
                $query
            );
        } catch (\Exception $e) {
            throw new \Exception('Error preparing statement: ' . $e->getMessage());
        }

        try {
            $statement->execute($this->queryParameters);
        } catch (\Exception $e) {
            throw new \Exception('Error executing query: ' . $e->getMessage());
        }

        return $statement;
    }

    private function fetchAllRows(PDOStatement $statement): array
    {
        for ($rows = [];;) {
            if (($row = $this->fetchRow($statement)) === false) {
                break;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function fetchRow(PDOStatement $statement)
    {
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return ($row === false) ? false : new Row($row);
    }

    public function findOne(): Row
    {
        /* force limit to 1 */
        $this->limit = 1;

        /* execute query and get PDOStatement */
        $statement = $this->executeQuery(
            $this->buildQuery()
        );

        return $this->fetchRow($statement);
    }

    public function findMany(): ?array
    {
        /* execute query and get PDOStatement */
        $statement = $this->executeQuery(
            $this->buildQuery()
        );

        /* fetch all rows from result */
        $rows = $this->fetchAllRows($statement);

        /* if table is mapped to a model, wrap all rows in a new model instance */
        if ($this->connection->getClassForTable($this->table)) {
            foreach ($rows as $i => $row) {
                $rows[$i] = $this->createModelInstance($row);
            }
        }

        return $rows;
    }

    private function createModelInstance(Row $row): Model
    {
        if ($this->modelFactory === null) {
            $this->modelFactory = new ModelFactory($this->connection);
        }

        $modelClass = $this->connection->getClassForTable($this->table);
        if ($modelClass !== null) {
            return $this->modelFactory->make($row, $modelClass);
        }
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
