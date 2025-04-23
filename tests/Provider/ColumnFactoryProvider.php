<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Column\BinaryColumn;
use Yiisoft\Db\Mssql\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function pseudoTypes(): array
    {
        $values = parent::pseudoTypes();

        $values['uuid_pk_seq'][1] = new StringColumn(ColumnType::UUID, primaryKey: true, autoIncrement: true, defaultValue: new Expression('newsequentialid()'));

        return $values;
    }

    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['bit', ColumnType::BOOLEAN, BooleanColumn::class],
            ['tinyint', ColumnType::TINYINT, IntegerColumn::class],
            ['smallint', ColumnType::SMALLINT, IntegerColumn::class],
            ['int', ColumnType::INTEGER, IntegerColumn::class],
            ['bigint', ColumnType::BIGINT, IntegerColumn::class],
            ['numeric', ColumnType::DECIMAL, DoubleColumn::class],
            ['decimal', ColumnType::DECIMAL, DoubleColumn::class],
            ['float', ColumnType::FLOAT, DoubleColumn::class],
            ['real', ColumnType::FLOAT, DoubleColumn::class],
            ['double', ColumnType::DOUBLE, DoubleColumn::class],
            ['smallmoney', ColumnType::MONEY, StringColumn::class],
            ['money', ColumnType::MONEY, StringColumn::class],
            ['smalldatetime', ColumnType::DATETIME, DateTimeColumn::class],
            ['datetime', ColumnType::DATETIME, DateTimeColumn::class],
            ['datetime2', ColumnType::DATETIME, DateTimeColumn::class],
            ['datetimeoffset', ColumnType::DATETIMETZ, DateTimeColumn::class],
            ['time', ColumnType::TIME, DateTimeColumn::class],
            ['date', ColumnType::DATE, DateTimeColumn::class],
            ['char', ColumnType::CHAR, StringColumn::class],
            ['varchar', ColumnType::STRING, StringColumn::class],
            ['text', ColumnType::TEXT, StringColumn::class],
            ['nchar', ColumnType::CHAR, StringColumn::class],
            ['nvarchar', ColumnType::STRING, StringColumn::class],
            ['ntext', ColumnType::TEXT, StringColumn::class],
            ['binary', ColumnType::BINARY, BinaryColumn::class],
            ['varbinary', ColumnType::BINARY, BinaryColumn::class],
            ['image', ColumnType::BINARY, BinaryColumn::class],
            ['timestamp', ColumnType::BINARY, BinaryColumn::class],
            ['hierarchyid', ColumnType::STRING, StringColumn::class],
            ['uniqueidentifier', ColumnType::UUID, StringColumn::class],
            ['sql_variant', ColumnType::STRING, StringColumn::class],
            ['xml', ColumnType::STRING, StringColumn::class],
            ['table', ColumnType::STRING, StringColumn::class],
        ];
    }

    public static function defaultValueRaw(): array
    {
        $defaultValueRaw = parent::defaultValueRaw();

        $defaultValueRaw['(now())'][2] = new Expression('now()');

        $defaultValueRaw[] = [ColumnType::TEXT, '(NULL)', null];
        $defaultValueRaw[] = [ColumnType::TEXT, "(('str''ing'))", "str'ing"];
        $defaultValueRaw[] = [ColumnType::INTEGER, '((-1))', -1];
        $defaultValueRaw[] = [ColumnType::TIMESTAMP, '((now()))', new Expression('(now())')];
        $defaultValueRaw[] = [ColumnType::BOOLEAN, '((1))', true];
        $defaultValueRaw[] = [ColumnType::BOOLEAN, '((0))', false];
        $defaultValueRaw[] = [ColumnType::BINARY, '(0x737472696e67)', 'string'];

        return $defaultValueRaw;
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions['integer[]'] = ['int[]', new ArrayColumn(dbType: 'int', column: new IntegerColumn(dbType: 'int'))];

        return $definitions;
    }
}
