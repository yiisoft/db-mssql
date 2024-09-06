<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
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
            ['bit', ColumnType::BOOLEAN, BooleanColumnSchema::class],
            ['tinyint', ColumnType::TINYINT, IntegerColumnSchema::class],
            ['smallint', ColumnType::SMALLINT, IntegerColumnSchema::class],
            ['int', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['bigint', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['numeric', ColumnType::DECIMAL, DoubleColumnSchema::class],
            ['decimal', ColumnType::DECIMAL, DoubleColumnSchema::class],
            ['float', ColumnType::FLOAT, DoubleColumnSchema::class],
            ['real', ColumnType::FLOAT, DoubleColumnSchema::class],
            ['double', ColumnType::DOUBLE, DoubleColumnSchema::class],
            ['smallmoney', ColumnType::MONEY, StringColumnSchema::class],
            ['money', ColumnType::MONEY, StringColumnSchema::class],
            ['date', ColumnType::DATE, StringColumnSchema::class],
            ['time', ColumnType::TIME, StringColumnSchema::class],
            ['smalldatetime', ColumnType::DATETIME, StringColumnSchema::class],
            ['datetime', ColumnType::DATETIME, StringColumnSchema::class],
            ['datetime2', ColumnType::DATETIME, StringColumnSchema::class],
            ['datetimeoffset', ColumnType::DATETIME, StringColumnSchema::class],
            ['char', ColumnType::CHAR, StringColumnSchema::class],
            ['varchar', ColumnType::STRING, StringColumnSchema::class],
            ['text', ColumnType::TEXT, StringColumnSchema::class],
            ['nchar', ColumnType::CHAR, StringColumnSchema::class],
            ['nvarchar', ColumnType::STRING, StringColumnSchema::class],
            ['ntext', ColumnType::TEXT, StringColumnSchema::class],
            ['binary', ColumnType::BINARY, BinaryColumnSchema::class],
            ['varbinary', ColumnType::BINARY, BinaryColumnSchema::class],
            ['image', ColumnType::BINARY, BinaryColumnSchema::class],
            ['timestamp', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['hierarchyid', ColumnType::STRING, StringColumnSchema::class],
            ['uniqueidentifier', ColumnType::STRING, StringColumnSchema::class],
            ['sql_variant', ColumnType::STRING, StringColumnSchema::class],
            ['xml', ColumnType::STRING, StringColumnSchema::class],
            ['table', ColumnType::STRING, StringColumnSchema::class],
        ];
    }
}
