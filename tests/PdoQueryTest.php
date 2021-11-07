<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tomrf\Snek\Factory;
use Tomrf\Snek\Pdo\PdoConnection;
use Tomrf\Snek\Pdo\PdoCredentials;
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
            new PdoCredentials(
                PdoCredentials::DSN('sqlite', ':memory:')
            ),
            new Factory(PdoQueryBuilder::class),
            new Factory(PdoQueryExecuter::class),
        );
        $sql = file_get_contents('tests/sql/countries_schema.sql');
        self::$connection->getPdo()->exec($sql);
        $sql = file_get_contents('tests/sql/countries_data.sql');
        self::$connection->getPdo()->exec($sql);
    }

    public function testIsConnected()
    {
        $this->assertTrue(self::$connection->isConnected());
    }

    public function testSelectFindOneReturnsRow()
    {
        $row = $this->queryTestCountries()->select('*')->findOne();
        $this->assertInstanceOf(Row::class, $row);
    }

    public function testSelectFindManyReturnsArrayOfRows()
    {
        $rows = $this->queryTestCountries()->findMany();

        $this->assertIsArray($rows);
        $this->assertContainsOnlyInstancesOf(Row::class, $rows);
    }

    public function testSelectFindManyLimitOneReturnsArrayOfOneRow()
    {
        $rows = $this->queryTestCountries()->limit(1)->findMany();

        $this->assertCount(1, $rows);
    }

    public function testUnspecifiedSelectReturnsAllColumns()
    {
        $columns = ['id', 'phone', 'code', 'name', 'symbol', 'currency', 'continent', 'continent_code'];
        $row = $this->queryTestCountries()->findOne();
        foreach ($columns as $column) {
            $this->assertArrayHasKey($column, $row);
        }
    }

    public function testSelectAs()
    {
        $row = $this->queryTestCountries()->selectAs('symbol', 'currency_symbol')->findOne();
        $this->assertArrayHasKey('currency_symbol', $row);
    }

    public function testSelectRaw()
    {
        $row = $this->queryTestCountries()->selectRaw('COUNT()', 'RANDOM()', '"string"')->findOne();
        $this->assertEquals((int) $row['COUNT()'], 252);
        $this->assertEquals('string', $row['"string"']);
        $this->assertArrayHasKey('RANDOM()', $row);
    }

    public function testSelectRawAs()
    {
        $row = $this->queryTestCountries()->selectRawAs('COUNT()', 'number_of_rows')->findOne();
        $this->assertArrayHasKey('number_of_rows', $row);
        $this->assertEquals('252', $row['number_of_rows']);
    }

    // helpers
    private function queryTestCountries(): PdoQueryBuilder
    {
        return self::$connection->queryTable('countries');
    }
}
