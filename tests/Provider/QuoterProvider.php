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
            ['test', '[test]' ],
            ['te`st', '[te`st]'],
            ['te\'st', '[te\'st]'],
            ['te"st', '[te"st]'],
            ['current-table-name', '[current-table-name]'],
            ['[current-table-name]', '[current-table-name]'],
        ];
    }

    /**
     * @return string[][]
     */
    public function unquoteSimpleColumnName(): array
    {
        return [
            ['*', '*'],
            ['`*`', '`*`'],
            ['[[*]]', '[*]'],
            ['{{*}}', '{{*}}'],
            ['table.column', 'table.column'],
            ['[[table.column]]', '[table.column]'],
            ['{{table}}.column', '{{table}}.column'],
        ];
    }

    /**
     * @return string[][]
     */
    public function unquoteSimpleTableName(): array
    {
        return [
            ['test', 'test'],
            ['`test`', '`test`'],
            ['[[test]]', '[test]'],
            ['{{test}}', '{{test}}'],
            ['table.column', 'table.column'],
            ['[[table.column]]', '[table.column]'],
            ['{{table.column}}', '{{table.column}}'],
        ];
    }
}
