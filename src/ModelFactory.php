<?php

namespace Tomrf\Snek;

class ModelFactory
{
    public function make(string $class, Row $row): Model
    {
        return new $class($row, $this->connection);
    }
}
