<?php

namespace Tomrf\Snek;

class ModelFactory
{
    public function __construct(
        private ?Connection $connection = null
    ) {}

    public function make(string $class, Row $row): Model
    {
        return new $class($row, $this->connection);
    }
}
