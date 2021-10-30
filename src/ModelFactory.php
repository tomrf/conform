<?php

namespace Tomrf\Snek;

class ModelFactory
{
    public function __construct(
        private Connection $connection
    ) {}

    public function make(Row $row, string $class): Model
    {
        return new $class($this->connection, $row);
    }
}
