<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\SqlParser;
use Yiisoft\Db\Tests\AbstractSqlParserTest;

/**
 * @group mssql
 */
final class SqlParserTest extends AbstractSqlParserTest
{
    /** @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SqlParserProvider::getNextPlaceholder */
    public function testGetNextPlaceholder(string $sql, ?string $expectedPlaceholder, ?int $expectedPosition): void
    {
        parent::testGetNextPlaceholder($sql, $expectedPlaceholder, $expectedPosition);
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
