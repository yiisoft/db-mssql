<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Mssql\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['bit', 'boolean', BooleanColumnSchema::class],
            ['tinyint', 'tinyint', IntegerColumnSchema::class],
            ['smallint', 'smallint', IntegerColumnSchema::class],
            ['int', 'integer', IntegerColumnSchema::class],
            ['bigint', 'bigint', IntegerColumnSchema::class],
            ['numeric', 'decimal', DoubleColumnSchema::class],
            ['decimal', 'decimal', DoubleColumnSchema::class],
            ['float', 'float', DoubleColumnSchema::class],
            ['real', 'float', DoubleColumnSchema::class],
            ['double', 'double', DoubleColumnSchema::class],
            ['smallmoney', 'money', StringColumnSchema::class],
            ['money', 'money', StringColumnSchema::class],
            ['date', 'date', StringColumnSchema::class],
            ['time', 'time', StringColumnSchema::class],
            ['smalldatetime', 'datetime', StringColumnSchema::class],
            ['datetime', 'datetime', StringColumnSchema::class],
            ['datetime2', 'datetime', StringColumnSchema::class],
            ['datetimeoffset', 'datetime', StringColumnSchema::class],
            ['char', 'char', StringColumnSchema::class],
            ['varchar', 'string', StringColumnSchema::class],
            ['text', 'text', StringColumnSchema::class],
            ['nchar', 'char', StringColumnSchema::class],
            ['nvarchar', 'string', StringColumnSchema::class],
            ['ntext', 'text', StringColumnSchema::class],
            ['binary', 'binary', BinaryColumnSchema::class],
            ['varbinary', 'binary', BinaryColumnSchema::class],
            ['image', 'binary', BinaryColumnSchema::class],
            ['timestamp', 'timestamp', StringColumnSchema::class],
            ['hierarchyid', 'string', StringColumnSchema::class],
            ['uniqueidentifier', 'string', StringColumnSchema::class],
            ['sql_variant', 'string', StringColumnSchema::class],
            ['xml', 'string', StringColumnSchema::class],
            ['table', 'string', StringColumnSchema::class],
        ];
    }
}
