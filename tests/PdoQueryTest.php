<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tomrf\Snek\Factory;
use Tomrf\Snek\Pdo\PdoConnection;
use Tomrf\Snek\Pdo\PdoConnectionCredentials;
use Tomrf\Snek\Pdo\PdoQueryBuilder;
use Tomrf\Snek\Pdo\PdoQueryExecuter;
use Tomrf\Snek\Row;

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
            new Factory(PdoQueryBuilder::class),
            new Factory(PdoQueryExecuter::class),
        );
        $sql = file_get_contents('tests/sql/countries_schema.sql');
        self::$connection->getPdo()->exec($sql);
        $sql = file_get_contents('tests/sql/countries_data.sql');
        self::$connection->getPdo()->exec($sql);
    }

    public function testIsConnected(): void
    {
        static::assertTrue(self::$connection->isConnected());
    }

    public function testSelectFindOneReturnsRow(): void
    {
        $row = $this->queryTestCountries()->select('*')->findOne();
        static::assertInstanceOf(Row::class, $row);
    }

    public function testSelectFindManyReturnsArrayOfRows(): void
    {
        $rows = $this->queryTestCountries()->findMany();

        static::assertIsArray($rows);
        static::assertContainsOnlyInstancesOf(Row::class, $rows);
    }

    public function testSelectFindManyLimitOneReturnsArrayOfOneRow(): void
    {
        $rows = $this->queryTestCountries()->limit(1)->findMany();

        static::assertCount(1, $rows);
    }

    public function testUnspecifiedSelectReturnsAllColumns(): void
    {
        $columns = ['id', 'phone', 'code', 'name', 'symbol', 'currency', 'continent', 'continent_code'];
        $row = $this->queryTestCountries()->findOne();
        foreach ($columns as $column) {
            static::assertArrayHasKey($column, $row);
        }
    }

    public function testSelectAs(): void
    {
        $row = $this->queryTestCountries()->selectAs('symbol', 'currency_symbol')->findOne();
        static::assertArrayHasKey('currency_symbol', $row);
    }

    public function testSelectRaw(): void
    {
        $row = $this->queryTestCountries()->selectRaw('COUNT()', 'RANDOM()', '"string"')->findOne();
        static::assertSame((int) $row['COUNT()'], 252);
        static::assertSame('string', $row['"string"']);
        static::assertArrayHasKey('RANDOM()', $row);
    }

    public function testSelectRawAs(): void
    {
        $row = $this->queryTestCountries()->selectRawAs('COUNT()', 'number_of_rows')->findOne();
        static::assertArrayHasKey('number_of_rows', $row);
        static::assertSame('252', $row['number_of_rows']);
    }

    // helpers
    private function queryTestCountries(): PdoQueryBuilder
    {
        return self::$connection->queryTable('countries');
    }
}
