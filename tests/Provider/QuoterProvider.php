<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

final class QuoterProvider
{
    /**
     * @return string[][]
     */
    public function columnNames(): array
    {
        return [
            ['*', '*'],
            ['table.*', '[table].*'],
            ['[table].*', '[table].*'],
            ['table.column', '[table].[column]'],
            ['[table].column', '[table].[column]'],
            ['table.[column]', '[table].[column]'],
            ['[table].[column]', '[table].[column]'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleColumnNames(): array
    {
        return [
            ['test', '[test]', 'test'],
            ['[test]', '[test]', 'test'],
            ['*', '*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleTableNames(): array
    {
        return [
            ['test', 'test', ],
            ['te`st', 'te`st', ],
            ['te\'st', 'te\'st', ],
            ['te"st', 'te"st', ],
            ['current-table-name', 'current-table-name', ],
            ['[current-table-name]', 'current-table-name', ],
        ];
    }

    public function tableName(): array
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

    public function tableParts(): array
    {
        return [
            ['animal', 'animal',],
            ['dbo.animal', 'animal', 'dbo'],
            ['[dbo].[animal]', 'animal', 'dbo'],
            ['[other].[animal2]', 'animal2', 'other'],
            ['other.[animal2]', 'animal2', 'other'],
            ['other.animal2', 'animal2', 'other'],
            ['catalog.other.animal2', 'animal2', 'other', 'catalog'],
            ['server.catalog.other.animal2', 'animal2', 'other', 'catalog', 'server'],
            ['unknown_part.server.catalog.other.animal2', 'animal2', 'other', 'catalog', 'server'],
        ];
    }
}
