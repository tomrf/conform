<?php

namespace Tomrf\Snek;

class QueryBuilderFactory
{
    public function __construct(
        private string $queryBuilderClass,
        private string $queryExecuterClass,
    ) {
    }

    public function makeQueryBuilder(
        Connection $connection,
        ?ModelFactory $modelFactory = null,
        ?string $modelClass = null
    ): object // @todo QueryBuilderInterface
    {
        return new $this->queryBuilderClass(
            new $this->queryExecuterClass(
                $connection,
                $modelFactory,
                $modelClass
            )
        );
    }
}
