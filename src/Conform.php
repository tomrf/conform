<?php

declare(strict_types=1);

namespace Tomrf\Conform;

use Closure;
use Tomrf\Conform\Interface\ConnectionInterface;
use Tomrf\Conform\Interface\QueryBuilderFactoryInterface;
use Tomrf\Conform\Interface\QueryBuilderInterface;
use Tomrf\Conform\Interface\QueryExecuterFactoryInterface;
use Tomrf\Conform\Interface\QueryExecuterInterface;

class Conform
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected QueryBuilderFactoryInterface $queryBuilderFactory,
        protected QueryExecuterFactoryInterface $queryExecutorFactory,
        protected ?Closure $callbackBeforeExecute = null,
        protected ?Closure $callbackAfterExecute = null
    ) {
    }

    /**
     * Return the active connection.
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function query(): QueryBuilderFactoryInterface
    {
        return $this->queryBuilderFactory;
    }

    public function execute(
        QueryBuilderInterface|string $query,
        array $parameters = []
    ): QueryExecuterInterface {
        if (null !== $this->callbackBeforeExecute) {
            \call_user_func(
                $this->callbackBeforeExecute,
                $query
            );
        }

        $timestamp = microtime(true);

        $queryExecuter = $this->queryExecutorFactory->execute(
            $this->connection,
            $query,
            $parameters
        );

        if (null !== $this->callbackAfterExecute) {
            \call_user_func(
                $this->callbackAfterExecute,
                $query,
                $queryExecuter,
                (microtime(true) - $timestamp)
            );
        }

        return $queryExecuter;
    }
}
