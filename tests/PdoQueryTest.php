<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tomrf\Conform\Data\Row;
use Tomrf\Conform\Factory\Factory;
use Tomrf\Conform\Pdo\PdoConnection;
use Tomrf\Conform\Pdo\PdoConnectionCredentials;
use Tomrf\Conform\Pdo\PdoQueryExecuter;
use Tomrf\Conform\SqlQueryBuilder;

/**
 * @internal
 * @coversNothing
 */
final class PdoQueryTest extends TestCase
{
    private static PdoConnection $connection;

    public static function setUpBeforeClass(): void
    {
        self::$connection = new PdoConnection(
            new PdoConnectionCredentials(
                PdoConnectionCredentials::DSN('sqlite', ':memory:')
            ),
            new Factory(SqlQueryBuilder::class),
            new Factory(PdoQueryExecuter::class),
        );
        $sql = file_get_contents('tests/sql/countries_schema.sql');
        self::$connection->getPdo()->exec($sql);
        $sql = file_get_contents('tests/sql/countries_data.sql');
        self::$connection->getPdo()->exec($sql);
    }

    public function test_connection_is_connected(): void
    {
        static::assertTrue(self::$connection->isConnected());
    }

    public function test_select_all_find_one_returns_instance_of_row(): void
    {
        $row = $this->queryTestCountries()->select('*')->findOne();
        static::assertInstanceOf(Row::class, $row);
    }

    public function test_select_all_find_many_returns_array_of_row(): void
    {
        $rows = $this->queryTestCountries()->select('*')->findMany();

        static::assertIsArray($rows);
        static::assertContainsOnlyInstancesOf(Row::class, $rows);
    }

    public function test_select_find_many_limit_1_returns_array_of_one_row(): void
    {
        $rows = $this->queryTestCountries()->limit(1)->findMany();

        static::assertIsArray($rows);
        static::assertCount(1, $rows);
        static::assertContainsOnlyInstancesOf(Row::class, $rows);
    }

    public function test_unspecified_select_returns_all_columns(): void
    {
        $columns = ['id', 'phone', 'code', 'name', 'symbol', 'currency', 'continent', 'continent_code'];
        $row = $this->queryTestCountries()->findOne();
        foreach ($columns as $column) {
            static::assertArrayHasKey($column, $row);
        }
    }

    public function test_select_as(): void
    {
        $row = $this->queryTestCountries()->selectAs('symbol', 'currency_symbol')->findOne();
        static::assertArrayHasKey('currency_symbol', $row);
    }

    public function test_select_raw(): void
    {
        $row = $this->queryTestCountries()->selectRaw('COUNT()', 'RANDOM()', '"string"')->findOne();
        static::assertSame((int) $row['COUNT()'], 252);
        static::assertSame('string', $row['"string"']);
        static::assertArrayHasKey('RANDOM()', $row);
    }

    public function test_select_raw_as(): void
    {
        $row = $this->queryTestCountries()->selectRawAs('COUNT()', 'number_of_rows')->findOne();
        static::assertArrayHasKey('number_of_rows', $row);
        static::assertSame('252', $row['number_of_rows']);
    }

    // helpers
    private function queryTestCountries(): SqlQueryBuilder
    {
        return self::$connection->queryTable('countries');
    }
}
