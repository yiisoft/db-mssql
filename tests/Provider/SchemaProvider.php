<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use DateTimeImmutable;
use DateTimeZone;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Mssql\Column\BinaryColumn;
use Yiisoft\Db\Mssql\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Support\Assert;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'int_col' => new IntegerColumn(
                        dbType: 'int',
                        notNull: true,
                        size: 10,
                        scale: 0,
                    ),
                    'int_col2' => new IntegerColumn(
                        dbType: 'int',
                        size: 10,
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'tinyint_col' => new IntegerColumn(
                        ColumnType::TINYINT,
                        dbType: 'tinyint',
                        size: 3,
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'smallint_col' => new IntegerColumn(
                        ColumnType::SMALLINT,
                        dbType: 'smallint',
                        size: 5,
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'char_col' => new StringColumn(
                        ColumnType::CHAR,
                        dbType: 'char',
                        notNull: true,
                        size: 100,
                    ),
                    'char_col2' => new StringColumn(
                        dbType: 'varchar',
                        size: 100,
                        defaultValue: 'something',
                    ),
                    'char_col3' => new StringColumn(
                        ColumnType::TEXT,
                        dbType: 'text',
                        size: 2147483647,
                    ),
                    'float_col' => new DoubleColumn(
                        ColumnType::DECIMAL,
                        dbType: 'decimal',
                        notNull: true,
                        size: 4,
                        scale: 3,
                    ),
                    'float_col2' => new DoubleColumn(
                        ColumnType::FLOAT,
                        dbType: 'float',
                        size: 53,
                        defaultValue: 1.23,
                    ),
                    'blob_col' => new BinaryColumn(
                        dbType: 'varbinary',
                    ),
                    'numeric_col' => new DoubleColumn(
                        ColumnType::DECIMAL,
                        dbType: 'decimal',
                        size: 5,
                        scale: 2,
                        defaultValue: 33.22,
                    ),
                    'datetime_col' => new DateTimeColumn(
                        dbType: 'datetime',
                        notNull: true,
                        size: 3,
                        defaultValue: new DateTimeImmutable('2002-01-01 00:00:00', new DateTimeZone('UTC')),
                        shouldConvertTimezone: true,
                    ),
                    'bool_col' => new BooleanColumn(
                        dbType: 'bit',
                        notNull: true,
                    ),
                    'bool_col2' => new BooleanColumn(
                        dbType: 'bit',
                        defaultValue: true,
                    ),
                    'json_col' => new JsonColumn(
                        dbType: 'nvarchar',
                        defaultValue: ['a' => 1],
                        check: '(isjson([json_col])>(0))',
                    ),
                ],
                'tableName' => 'type',
            ],
            [
                [
                    'id' => new IntegerColumn(
                        dbType: 'int',
                        primaryKey: true,
                        notNull: true,
                        autoIncrement: true,
                        size: 10,
                        scale: 0,
                    ),
                    'type' => new StringColumn(
                        dbType: 'varchar',
                        notNull: true,
                        size: 255,
                    ),
                ],
                'animal',
            ],
        ];
    }

    public static function constraints(): array
    {
        $constraints = parent::constraints();

        Assert::setPropertyValue($constraints['1: check'][2][0], 'expression', "([C_check]<>'')");
        $constraints['1: default'][2] = [new DefaultValue('', ['C_default'], '((0))')];
        $constraints['2: default'][2] = [];
        Assert::setPropertyValue($constraints['3: foreign key'][2][0], 'foreignSchemaName', 'dbo');
        $constraints['3: index'][2] = [];
        $constraints['3: default'][2] = [];
        $constraints['4: default'][2] = [];

        return $constraints;
    }

    public static function resultColumns(): array
    {
        return [
            [null, []],
            [null, ['sqlsrv:decl_type' => '']],
            [new IntegerColumn(dbType: 'int', name: 'int_col', size: 10), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'int',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'int_col',
                'len' => 10,
                'precision' => 0,
            ]],
            [new IntegerColumn(ColumnType::TINYINT, dbType: 'tinyint', name: 'tinyint_col', size: 3), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'tinyint',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'tinyint_col',
                'len' => 3,
                'precision' => 0,
            ]],
            [new IntegerColumn(ColumnType::SMALLINT, dbType: 'smallint', name: 'smallint_col', size: 5), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'smallint',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'smallint_col',
                'len' => 5,
                'precision' => 0,
            ]],
            [new StringColumn(ColumnType::CHAR, dbType: 'char', name: 'char_col', size: 100), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'char',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'char_col',
                'len' => 100,
                'precision' => 0,
            ]],
            [new StringColumn(dbType: 'varchar', name: 'char_col2', size: 100), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'varchar',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'char_col2',
                'len' => 100,
                'precision' => 0,
            ]],
            [new StringColumn(ColumnType::TEXT, dbType: 'text', name: 'char_col3', size: 2147483647), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'text',
                'native_type' => 'string',
                'table' => 'type',
                'pdo_type' => 2,
                'name' => 'char_col3',
                'len' => 2147483647,
                'precision' => 0,
            ]],
            [new DoubleColumn(ColumnType::DECIMAL, dbType: 'decimal', name: 'float_col', size: 4, scale: 3), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'decimal',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'float_col',
                'len' => 4,
                'precision' => 3,
            ]],
            [new DoubleColumn(ColumnType::FLOAT, dbType: 'float', name: 'float_col2', size: 53), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'float',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'float_col2',
                'len' => 53,
                'precision' => 0,
            ]],
            [new BinaryColumn(dbType: 'varbinary', name: 'blob_col', size: 0), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'varbinary',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'blob_col',
                'len' => 0,
                'precision' => 0,
            ]],
            [new DateTimeColumn(dbType: 'datetime', name: 'datetime_col', size: 3), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'datetime',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'datetime_col',
                'len' => 23,
                'precision' => 3,
            ]],
            [new DateTimeColumn(dbType: 'datetime2', name: 'datetime2_col', size: 7), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'datetime2',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'datetime2_col',
                'len' => 27,
                'precision' => 7,
            ]],
            [new BooleanColumn(dbType: 'bit', name: 'bool_col', size: 1), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'bit',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'bool_col',
                'len' => 1,
                'precision' => 0,
            ]],
            [new StringColumn(dbType: 'nvarchar', name: 'json_col'), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'nvarchar',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'json_col',
                'len' => 0,
                'precision' => 0,
            ]],
            [new DoubleColumn(ColumnType::DECIMAL, dbType: 'numeric', name: '2.5', size: 2, scale: 1), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'numeric',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => '2.5',
                'len' => 2,
                'precision' => 1,
            ]],
            [new StringColumn(dbType: 'varchar', name: 'string', size: 6), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'varchar',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'string',
                'len' => 6,
                'precision' => 0,
            ]],
        ];
    }

    public static function tableSchemaWithDbSchemes(): array
    {
        return [
            ['animal', 'animal',],
            ['dbo.animal', 'animal',],
            ['[dbo].[animal]', 'animal',],
            ['[other].[animal2]', 'other.animal2',],
            ['other.[animal2]', 'other.animal2',],
            ['other.animal2', 'other.animal2',],
            ['catalog.other.animal2', 'catalog.other.animal2',],
            ['server.catalog.other.animal2', 'server.catalog.other.animal2',],
            ['unknown_part.server.catalog.other.animal2', 'server.catalog.other.animal2',],
        ];
    }
}
