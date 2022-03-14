<?php

declare(strict_types=1);

namespace Tomrf\Conform\Pdo;

use DomainException;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Tomrf\Conform\Row;

class PdoQueryBuilder
{
    protected string $table = '';

    /**
     * @var array<array>
     */
    protected array $querySelect = [];

    /**
     * @var array<array>
     */
    protected array $queryJoin = [];

    /**
     * @var array<array>
     */
    protected array $queryWhere = [];
    /**
     * @var array<array>
     */
    protected array $queryOrderBy = [];
    /**
     * @var array<array>
     */
    protected array $queryInsert = [];
    /**
     * @var array<string,mixed>
     */
    protected array $queryParameters = [];

    protected int $queryLimit = -1;
    protected int $queryLimitOffset = -1;

    protected ?string $onDuplicate = null;

    public function __construct(
        protected PdoQueryExecuter $queryExecuter,
    ) {
    }

    public function forTable(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function set(string $column, mixed $value): self
    {
        $key = trim($column);

        $this->queryInsert[$key] = [
            'value' => $value,
            'raw' => false,
        ];

        return $this;
    }

    public function setRaw(string $column, string $expression): self
    {
        $key = trim($column);

        $this->queryInsert[$key] = [
            'value' => $expression,
            'raw' => true,
        ];

        return $this;
    }

    public function onDuplicateKey(string $expression): self
    {
        $this->onDuplicate = trim($expression);

        return $this;
    }

    /**
     * @param array<string, mixed> $keyValue
     *
     * @throws Exception
     */
    public function insert(array $keyValue): string|false
    {
        foreach ($keyValue as $key => $value) {
            $this->set($key, $value);
        }

        $this->assertQueryState();

        return $this->queryExecuter->insert(
            $this->buildQuery(),
            $this->queryParameters
        );
    }

    public function select(string ...$params): self
    {
        foreach ($params as $column) {
            $this->querySelect[] = [
                'expression' => $this->quoteExpression(trim($column)),
            ];
        }

        return $this;
    }

    public function selectAs(string $expression, string $alias): self
    {
        $this->querySelect[] = [
            'expression' => $this->quoteExpression(trim($expression)),
            'alias' => $this->quoteString(trim($alias)),
        ];

        return $this;
    }

    public function selectRaw(string ...$params): self
    {
        foreach ($params as $expression) {
            $this->querySelect[] = [
                'expression' => trim($expression),
            ];
        }

        return $this;
    }

    public function selectRawAs(string $expression, string $alias): self
    {
        $this->querySelect[] = [
            'expression' => trim($expression),
            'alias' => $this->quoteString(trim($alias)),
        ];

        return $this;
    }

    public function alias(string $expression, string $alias): self
    {
        foreach ($this->querySelect as $i => $select) {
            if ($select['expression'] === $expression) {
                $this->querySelect[$i]['alias'] = $this->quoteString(trim($alias));
            }
        }

        return $this;
    }

    public function join(string $table, string $joinCondition): self
    {
        $this->queryJoin[] = [
            'table' => trim($table),
            'condition' => trim($joinCondition),
        ];

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->queryWhere[] = [
            'left' => trim($column),
            'right' => \is_string($value) ? trim($value) : $value,
            'operator' => trim($operator),
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
        return $this->where($column, 'IS', 'NULL');
    }

    public function whereNotNull(string $column): self
    {
        return $this->where($column, 'IS NOT', 'NULL');
    }

    /**
     * @param null|array<string,mixed> $namedParameters
     *
     * @return PdoQueryBuilder
     */
    public function whereRaw(string $clause, ?array $namedParameters = null): self
    {
        $this->queryWhere[] = [
            'raw' => $clause,
            'parameters' => $namedParameters,
        ];

        return $this;
    }

    public function orderByAsc(string $column): self
    {
        $this->queryOrderBy[] = [
            'column' => trim($column),
            'direction' => 'ASC',
        ];

        return $this;
    }

    public function orderByDesc(string $column): self
    {
        $this->queryOrderBy[] = [
            'column' => trim($column),
            'direction' => 'DESC',
        ];

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Illegal (negative) LIMIT value specified');
        }

        $this->queryLimit = $limit;

        if (null !== $offset) {
            return $this->offset($offset);
        }

        return $this;
    }

    public function offset(int $offset, ?int $limit = null): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Illegal (negative) OFFSET value specified');
        }
        $this->queryLimitOffset = $offset;

        if (null !== $limit) {
            return $this->limit($limit);
        }

        return $this;
    }

    public function findOne(): Row|bool
    {
        $this->queryLimit = 1;
        $this->assertQueryState();

        return $this->queryExecuter->findOne(
            $this->buildQuery(),
            $this->queryParameters
        );
    }

    /**
     * @throws Exception
     *
     * @return null|array<int,mixed>
     */
    public function findMany(): ?array
    {
        $this->assertQueryState();

        return $this->queryExecuter->findMany(
            $this->buildQuery(),
            $this->getQueryParameters()
        );
    }

    /**
     * @return array<string,mixed>
     */
    protected function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    protected function buildQuerySelectExpression(): string
    {
        if (0 === \count($this->querySelect)) {
            return '*';
        }

        $selectExpression = '';

        foreach ($this->querySelect as $key => $select) {
            $selectExpression .= sprintf(
                '%s%s',
                $select['expression'],
                isset($select['alias']) ? (' AS '.$select['alias']) : ''
            );

            if ($key !== array_key_last($this->querySelect)) {
                $selectExpression .= ',';
            }
        }

        return $selectExpression;
    }

    protected function buildQueryJoinClause(): string
    {
        $joinClause = '';
        foreach ($this->queryJoin as $join) {
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

        if (0 === \count($this->queryInsert)) {
            return '';
        }

        foreach ($this->queryInsert as $column => $valueData) {
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

    protected function buildQueryWhereCondition(): string
    {
        if (0 === \count($this->queryWhere)) {
            return '';
        }

        $whereCondition = ' WHERE ';

        foreach ($this->queryWhere as $key => $where) {
            if (isset($where['raw'])) {
                $whereCondition .= sprintf(
                    '%s%s',
                    $where['raw'],
                    ($key !== array_key_last($this->queryWhere)) ? ' AND ' : ''
                );
                if (null !== $where['parameters']) {
                    $this->queryParameters = array_merge($this->queryParameters, $where['parameters']);
                }
            } elseif ('IS' === mb_substr(mb_strtoupper($where['operator']), 0, 2)) {
                $whereCondition .= sprintf(
                    '%s %s %s%s',
                    $this->quoteExpression($where['left']),
                    mb_strtoupper($where['operator']),
                    $where['right'],
                    ($key !== array_key_last($this->queryWhere)) ? ' AND ' : ''
                );
            } else {
                if (isset($this->queryParameters[$where['left']])) {
                    throw new RuntimeException(sprintf(
                        'Duplicate parameter "%s" in WHERE condition for table "%s"',
                        $where['left'],
                        $this->table
                    ));
                }

                $parameterName = str_replace('.', '_', $where['left']);
                if (\is_array($parameterName)) {
                    throw new RuntimeException('Unexpected value: got array from str_replace()');
                }

                $this->queryParameters[$parameterName] = $where['right'];

                $whereCondition .= sprintf(
                    '%s%s:%s%s',
                    $this->quoteExpression($where['left']),
                    $where['operator'],
                    $parameterName,
                    ($key !== array_key_last($this->queryWhere)) ? ' AND ' : ''
                );
            }
        }

        return $whereCondition;
    }

    protected function buildQueryOrderByClause(): string
    {
        if (0 === \count($this->queryOrderBy)) {
            return '';
        }

        $orderByClause = ' ORDER BY ';
        foreach ($this->queryOrderBy as $key => $orderBy) {
            $orderByClause .= sprintf(
                '%s %s%s',
                $this->quoteExpression($orderBy['column']),
                $orderBy['direction'],
                ($key !== array_key_last($this->queryOrderBy)) ? ', ' : ''
            );
        }

        return $orderByClause;
    }

    protected function buildQuery(): string
    {
        if (\count($this->queryInsert) > 0) {
            return trim(sprintf(
                'INSERT INTO %s %s %s',
                $this->quoteExpression($this->table),
                $this->buildQueryInsertStatement(),
                $this->onDuplicate ? 'ON DUPLICATE KEY '.$this->onDuplicate : ''
            ));
        }

        return sprintf(
            'SELECT %s FROM %s%s%s%s%s%s',
            $this->buildQuerySelectExpression(),
            $this->quoteExpression($this->table),
            $this->buildQueryJoinClause(),
            $this->buildQueryWhereCondition(),
            $this->buildQueryOrderByClause(),
            (-1 !== $this->queryLimit) ? sprintf(' LIMIT %d', $this->queryLimit) : '',
            (-1 !== $this->queryLimitOffset) ? sprintf(' OFFSET %d', $this->queryLimitOffset) : ''
        );
    }

    protected function assertQueryState(): void
    {
        if (-1 !== $this->queryLimitOffset && -1 === $this->queryLimit) {
            throw new DomainException('Query validation failed: OFFSET specified without LIMIT clause');
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
