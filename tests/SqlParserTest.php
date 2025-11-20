<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Mssql\SqlParser;
use Yiisoft\Db\Mssql\Tests\Provider\SqlParserProvider;
use Yiisoft\Db\Tests\Common\CommonSqlParserTest;

/**
 * @group mssql
 */
final class SqlParserTest extends CommonSqlParserTest
{
    #[DataProviderExternal(SqlParserProvider::class, 'getNextPlaceholder')]
    public function testGetNextPlaceholder(string $sql, ?string $expectedPlaceholder, ?int $expectedPosition): void
    {
        parent::testGetNextPlaceholder($sql, $expectedPlaceholder, $expectedPosition);
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
