<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Mssql\Tests\TestCase;

final class SchemaProvider extends TestCase
{
    public function quoteTableNameDataProvider(): array
    {
        return [
            ['test', '[test]'],
            ['test.test', '[test].[test]'],
            ['test.test.test', '[test].[test].[test]'],
            ['[test]', '[test]'],
            ['[test].[test]', '[test].[test]'],
            ['test.[test.test]', '[test].[test.test]'],
            ['test.test.[test.test]', '[test].[test].[test.test]'],
            ['[test].[test.test]', '[test].[test.test]'],
        ];
    }

    public function getTableSchemaDataProvider(): array
    {
        return [
            ['[dbo].[profile]', 'profile'],
            ['dbo.profile', 'profile'],
            ['profile', 'profile'],
            ['dbo.[table.with.special.characters]', 'table.with.special.characters'],
        ];
    }
}
