<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Tests\AbstractQuoterTest;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 */
final class QuoterTest extends AbstractQuoterTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::columnNames
     */
    public function testQuoteColumnName(string $columnName, string $expectedQuotedColumnName): void
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteColumnName($columnName);

        $this->assertSame($expectedQuotedColumnName, $quoted);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::simpleColumnNames
     */
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColunName
    ): void {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteSimpleColumnName($columnName);

        $this->assertSame($expectedQuotedColumnName, $quoted);

        $unQuoted = $quoter->unquoteSimpleColumnName($quoted);

        $this->assertSame($expectedUnQuotedColunName, $unQuoted);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::simpleTableNames()
     */
    public function testQuoteSimpleTableName(string $tableName, string $expectedTableName): void
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();
        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteSimpleTableName($tableName));

        $this->assertSame($expectedTableName, $unQuoted);

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteTableName($tableName));

        $this->assertSame($expectedTableName, $unQuoted);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::tableName
     */
    public function testQuoteTableName(string $name, string $expectedName): void
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();
        $quotedName = $quoter->quoteTableName($name);

        $this->assertSame($expectedName, $quotedName);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QuoterProvider::tableParts
     */
    public function testQuoteTableParts(string $tableName, ...$expectedParts): void
    {
        $quoter = $this->getConnection()->getQuoter();

        $parts = $quoter->getTableNameParts($tableName);

        $this->assertEquals($expectedParts, array_reverse($parts));
    }

    public function testQuoteValue(): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();

        $this->assertSame(123, $quoter->quoteValue(123));
        $this->assertSame("'string'", $quoter->quoteValue('string'));
        $this->assertSame("'It''s interesting'", $quoter->quoteValue("It's interesting"));
    }
}
