<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tomrf\Conform\Conform;
use Tomrf\Conform\Data\Row;
use Tomrf\Conform\Factory\Factory;
use Tomrf\Conform\Pdo\PdoConnection;
use Tomrf\Conform\Pdo\PdoQueryExecutor;
use Tomrf\Conform\QueryBuilder;

/**
 * @internal
 * @coversNothing
 */
final class PdoQueryTest extends TestCase
{
    private static Conform $conform;

    public static function setUpBeforeClass(): void
    {
        self::$conform = new Conform(
            new PdoConnection(
                PdoConnection::DSN('sqlite', ':memory:')
            ),
            new Factory(QueryBuilder::class),
            new Factory(PdoQueryExecutor::class),
        );

        $sql = file_get_contents('tests/sql/countries_schema.sql');
        self::$conform->execute($sql)->getRowCount();

        $sql = file_get_contents('tests/sql/countries_data.sql');
        self::$conform->execute($sql)->getRowCount();
    }

    public function test_connection_is_connected(): void
    {
        static::assertTrue(
            self::$conform->getConnection()->isConnected()
        );
    }

    public function test_select_all_find_one_returns_instance_of_row(): void
    {
        $row = self::$conform->execute(
            self::$conform->query()->selectFrom('countries')
        )->findOne();

        static::assertInstanceOf(Row::class, $row);
    }

    public function test_select_all_find_many_returns_array_of_row(): void
    {
        $rows = self::$conform->execute(
            self::$conform->query()->selectFrom('countries')
        )->findMany();

        static::assertIsArray($rows);
        static::assertContainsOnlyInstancesOf(Row::class, $rows);
    }

    public function test_select_find_many_limit1_returns_array_of_one_row(): void
    {
        $rows = self::$conform->execute(
            self::$conform->query()
                ->selectFrom('countries')
                ->limit(1)
        )->findMany();

        static::assertIsArray($rows);
        static::assertCount(1, $rows);
        static::assertContainsOnlyInstancesOf(Row::class, $rows);
    }

    public function test_unspecified_select_returns_all_columns(): void
    {
        $columns = ['id', 'phone', 'code', 'name', 'symbol', 'currency', 'continent', 'continent_code'];

        $row = self::$conform->execute(
            self::$conform->query()
                ->selectFrom('countries')
        )->findOne();

        foreach ($columns as $column) {
            static::assertArrayHasKey($column, $row);
        }
    }

    public function test_select_as(): void
    {
        $row = self::$conform->execute(
            self::$conform->query()
                ->selectFrom('countries')
                ->selectAs('symbol', 'currency_symbol')
        )->findOne();

        static::assertArrayHasKey('currency_symbol', $row);
    }

    public function test_select_raw(): void
    {
        $row = self::$conform->execute(
            self::$conform->query()
                ->selectFrom('countries')
                ->selectRaw('COUNT()', 'RANDOM()', '"string"')
        )->findOne();

        static::assertSame($row['COUNT()']->asInteger(), 252);
        static::assertSame($row['"string"']->asString(), 'string');
        static::assertArrayHasKey('RANDOM()', $row);
    }

    public function test_select_raw_as(): void
    {
        $row = self::$conform->execute(
            self::$conform->query()
                ->selectFrom('countries')
                ->selectRawAs('COUNT()', 'number_of_rows')
        )->findOne();

        static::assertArrayHasKey('number_of_rows', $row);
        static::assertSame($row['number_of_rows']->asInteger(), 252);
    }

    public function test_querybuilder_sql_statement_syntax__select(): void
    {
        $queryBuilder = new QueryBuilder();
        static::assertSame(
            'SELECT `user`.* FROM `user`',
            $queryBuilder
                ->selectFrom('user')
                ->getQuery()
        );
        static::assertSame(
            'SELECT `id`, `username`, `email` FROM `user`',
            $queryBuilder
                ->selectFrom('user')
                ->select('id', 'username', 'email')
                ->getQuery()
        );
        static::assertSame(
            'SELECT `id`, `username`, `email`, `id`, NOW(), COUNT(id) AS '.
            '"user_count", MAX(id) AS "max_user_id" FROM `user`',
            $queryBuilder
                ->selectFrom('user')
                ->select('id')
                ->selectRaw('NOW()')
                ->selectRawAs('COUNT(id)', 'user_count')
                ->selectRawAs('MAX(id)', 'max_user_id')
                ->getQuery()
        );
        static::assertSame(
            'SELECT `id`, `username`, `email`, `id`, NOW(), COUNT(id) AS '.
            '"user_count", MAX(id) AS "max_user_id", `id`, `username`, `email`,'.
            ' `created_at` FROM `user` WHERE `id` != ? AND `username` IS NOT '.
            'NULL AND `email` = ? LIMIT 5 OFFSET 1',
            $queryBuilder
                ->selectFrom('user')
                ->select('id', 'username', 'email', 'created_at')
                ->whereNotEqual('id', 500)
                ->whereNotNull('username')
                ->whereEqual('email', 'mail@examle.com')
                ->limit(5)
                ->offset(1)
                ->getQuery()
        );
    }

    public function test_querybuilder_sql_statement_syntax__update(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder = $queryBuilder
            ->update('table')
            ->set('column', 'value')
            ->set('column2', 'value2')
            ->setRaw('column3', 'RAW_EXPRESSION')
            ->whereEqual('id', 1000)
        ;
        static::assertSame(
            'UPDATE `table` SET `column` = ?, `column2` = ?, `column3` = RAW_EXPRESSION WHERE `id` = ?',
            $queryBuilder->getQuery()
        );
        static::assertSame(
            ['value', 'value2', 1000],
            $queryBuilder->getQueryParameters()
        );
    }
}
