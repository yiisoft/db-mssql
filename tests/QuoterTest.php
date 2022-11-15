<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonQuoterTest;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QuoterTest extends CommonQuoterTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::columnNames()
     */
    public function testQuoteColumnNameWithDbGetQuoter(string $columnName, string $expected): void
    {
        parent::testQuoteColumnNameWithDbGetQuoter($columnName, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::simpleColumnNames()
     */
    public function testQuoteSimpleColumnNameWithDbGetQuoter(string $columnName, string $expected): void
    {
        parent::testQuoteSimpleColumnNameWithDbGetQuoter($columnName, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::simpleTableNames()
     */
    public function testQuoteSimpleTableNameWithDbGetQuoter(string $tableName, string $expected): void
    {
        parent::testQuoteSimpleTableNameWithDbGetQuoter($tableName, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::unquoteSimpleColumnName()
     */
    public function testUnquoteSimpleColumnNameWithDbGetQuoter(string $columnName, string $expected): void
    {
        parent::testUnquoteSimpleColumnNameWithDbGetQuoter($columnName, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::unquoteSimpleTableName()
     */
    public function testUnquoteSimpleTableNameWithDbGetQuoter(string $tableName, string $expected): void
    {
        parent::testUnquoteSimpleTableNameWithDbGetQuoter($tableName, $expected);
    }
}
