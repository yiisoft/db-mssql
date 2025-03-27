<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Mssql\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Support\AnyValue;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 10,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 10,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 3,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'smallint',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 5,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'char',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => 'something',
                    ],
                    'char_col3' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 2147483647,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col' => [
                        'type' => 'decimal',
                        'dbType' => 'decimal',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 4,
                        'scale' => 3,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'float',
                        'dbType' => 'float',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 53,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'varbinary',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'decimal',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 5,
                        'scale' => 2,
                        'defaultValue' => 33.22,
                    ],
                    'datetime_col' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 3,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'bool_col' => [
                        'type' => 'boolean',
                        'dbType' => 'bit',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'boolean',
                        'dbType' => 'bit',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => true,
                    ],
                    'json_col' => [
                        'type' => 'json',
                        'dbType' => 'nvarchar',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => ['a' => 1],
                        'check' => '(isjson([json_col])>(0))',
                    ],
                ],
                'tableName' => 'type',
            ],
            [
                [
                    'id' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => 10,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'type' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'animal',
            ],
        ];
    }

    public static function constraints(): array
    {
        $constraints = parent::constraints();

        $constraints['1: check'][2][0]->expression('([C_check]<>\'\')');
        $constraints['1: default'][2] = [];
        $constraints['1: default'][2][] = (new DefaultValueConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_default'])
            ->value('((0))');

        $constraints['2: default'][2] = [];

        $constraints['3: foreign key'][2][0]->foreignSchemaName('dbo');
        $constraints['3: index'][2] = [];
        $constraints['3: default'][2] = [];
        $constraints['4: default'][2] = [];

        return $constraints;
    }

    public static function resultColumns(): array
    {
        return [
            [null, []],
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
            [new StringColumn(ColumnType::DATETIME, dbType: 'datetime', name: 'datetime_col', size: 3), [
                'flags' => 0,
                'sqlsrv:decl_type' => 'datetime',
                'native_type' => 'string',
                'table' => '',
                'pdo_type' => 2,
                'name' => 'datetime_col',
                'len' => 23,
                'precision' => 3,
            ]],
            [new StringColumn(ColumnType::DATETIME, dbType: 'datetime2', name: 'datetime2_col', size: 7), [
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
