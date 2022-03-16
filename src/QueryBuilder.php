<?php

declare(strict_types=1);

namespace Tomrf\Conform;

class QueryBuilder
{
    protected string $table;
    protected string $statement;

    public function selectFrom(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('SELECT');

        return $this;
    }

    public function insertInto(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('INSERT INTO');

        return $this;
    }

    public function update(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('UPDATE');

        return $this;
    }

    public function deleteFrom(string $table): static
    {
        $this->setTable($table);
        $this->setStatement('DELETE FROM');

        return $this;
    }

    protected function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    protected function setStatement(string $type): void
    {
        $this->statement = $type;
    }
}
