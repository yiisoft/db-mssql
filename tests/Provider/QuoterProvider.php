<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

final class QuoterProvider extends \Yiisoft\Db\Tests\Provider\QuoterProvider
{
    /**
     * @return string[][]
     */
    public static function columnNames(): array
    {
        $columnNames = parent::columnNames();

        $columnNames[] = ['[column]', '[column]'];

        return $columnNames;
    }

    /**
     * @return string[][]
     */
    public static function simpleTableNames(): array
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

    public static function tableNameParts(): array
    {
        return [
            ['', ['name' => '']],
            ['[]', ['name' => '']],
            ['animal', ['name' => 'animal']],
            ['[animal]', ['name' => 'animal']],
            ['dbo.animal', ['schemaName' => 'dbo', 'name' => 'animal']],
            ['[dbo].[animal]', ['schemaName' => 'dbo', 'name' => 'animal']],
            ['[dbo].animal', ['schemaName' => 'dbo', 'name' => 'animal']],
            ['dbo.[animal]', ['schemaName' => 'dbo', 'name' => 'animal']],
            ['catalog.other.animal2', ['catalogName' => 'catalog', 'schemaName' => 'other', 'name' => 'animal2']],
            ['server.catalog.other.animal2', ['serverName' => 'server', 'catalogName' => 'catalog', 'schemaName' => 'other', 'name' => 'animal2']],
            ['unknown_part.server.catalog.other.animal2', ['serverName' => 'server', 'catalogName' => 'catalog', 'schemaName' => 'other', 'name' => 'animal2']],
        ];
    }
}
