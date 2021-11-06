<?php

namespace Tomrf\Snek;

class DatabaseManager
{
    public function __construct(
        private Connection $connection,
        private ModelFactory $modelFactory,
        private string $queryBuilderClass,
        private string $queryExecuterClass,
    ) {
    }

    public function queryTable(string $table): QueryBuilder
    {
        $queryBuilder = $this->makeQueryBuilder($this->connection, $this->modelFactory);

        return $queryBuilder->forTable($table);
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
