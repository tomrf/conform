<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

interface QueryBuilderFactoryInterface extends FactoryInterface
{
    public function selectFrom(string $table): QueryBuilderInterface;

    public function insertInto(string $table): QueryBuilderInterface;

    public function update(string $table): QueryBuilderInterface;

    public function deleteFrom(string $table): QueryBuilderInterface;
}
