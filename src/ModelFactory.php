<?php

namespace Tomrf\Snek;

class ModelFactory
{
    public function __construct(
        private Connection $connection
    ) {}

    public function make(Row $row, string $class): Model
    {
        return new $class($row);
    }

    public function fetch(string $modelClass, mixed $primaryKeyValue): Model
    {
        $table = $this->connection->getTableForClass($modelClass);
        $row = $this->connection->forTable($table)->where('id', $primaryKeyValue)->findOne();
        \var_dump($row);
        die();
    }
}
