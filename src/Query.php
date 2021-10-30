<?php

namespace Tomrf\Snek;

use Exception;
use PDO;

class Query
{
    private array $select = [];
    private array $where = [];

    public function __construct(
        private Connection $connection,
        private string $table,
    ) {}

    public function select(string $name): Query
    {
        $this->select[] = $name;
        return $this;
    }

    public function where(string $key, string|int|float|bool $value): Query
    {
        $this->where[$key] = $value;
        return $this;
    }

    public function findMany(): ?array
    {
        $qTable = $this->table;
        $qSelect = '*';
        $qWhere = '';
        $params = [];

        if (count($this->select) > 0) {
            $qSelect = '';
            foreach ($this->select as $column) {
                if (!$this->isValidColumnName($column)) {
                    throw new Exception('Invalid column name: ' . $column);
                }

                $qSelect .= '`'. $column . '`, ';
            }
            $qSelect = trim($qSelect, ', ');
        }

        $query = sprintf(
            "SELECT %s FROM `%s`",
            $qSelect, $qTable
        );

        if (count($this->where) > 0) {
            $qWhere = ' WHERE ';
            foreach ($this->where as $key => $value) {
                if (!$this->isValidColumnName($key)) {
                    throw new Exception('Invalid column name: ' . $key);
                }
                $params[$key] = $value;
                $qWhere .= '`' . $key . '` = :' . $key;
                if ($key !== \array_key_last($this->where)) {
                    $qWhere .= ' AND ';
                }
            }
            $query .= $qWhere;
        }

        // \var_dump($query, $params);die();

        $statement = $this->connection->getPdo()->prepare($query);
        $statement->execute($params);

        // $dataRows = $statement->fetchAll(PDO::FETCH_CLASS, RowData::class);
        $rows = [];
        while (1) {
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                break;
            }
            $rows[] = new Row($row);
        }

        $modelFactory = new ModelFactory($this->connection);
        $modelClass = $this->connection->getClassForTable($this->table);

        if ($modelClass !== null) {
            foreach ($rows as $i => $row) {
                // $rows[$i] = new $modelClass($row);
                $rows[$i] = $modelFactory->make($row, $modelClass);
            }
        }

        return $rows;
    }

    private function isValidColumnName(string $name): bool
    {
        $legal = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
        for ($i = 0; $i < strlen($name); $i++) {
            if (!strstr($legal, $name[$i])) {
                return false;
            }
        }
        return true;
    }
}
