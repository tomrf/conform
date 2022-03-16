<?php

declare(strict_types=1);

namespace Tomrf\Conform\Factory;

use Tomrf\Conform\Interface\QueryBuilderFactoryInterface;
use Tomrf\Conform\Interface\QueryBuilderInterface;

class QueryBuilderFactory extends Factory implements QueryBuilderFactoryInterface
{
    /**
     * @var class-string of QueryBuilderInterface
     */
    protected string $class;

    public function selectFrom(string $table): QueryBuilderInterface
    {
        return $this->make($table, 'SELECT');
    }

    public function insertInto(string $table): QueryBuilderInterface
    {
        return $this->make($table, 'INSERT INTO');
    }

    public function update(string $table): QueryBuilderInterface
    {
        return $this->make($table, 'UPDATE');
    }

    public function deleteFrom(string $table): QueryBuilderInterface
    {
        return $this->make($table, 'DELETE FROM');
    }
}
