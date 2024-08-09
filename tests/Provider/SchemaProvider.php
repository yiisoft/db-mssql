<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constraint\DefaultValueConstraint;
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
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'smallint',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'char(100)',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => 100,
                        'precision' => 100,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar(100)',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => 100,
                        'precision' => 100,
                        'scale' => null,
                        'defaultValue' => 'something',
                    ],
                    'char_col3' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col' => [
                        'type' => 'decimal',
                        'dbType' => 'decimal(4,3)',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => 4,
                        'precision' => 4,
                        'scale' => 3,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'float',
                        'dbType' => 'float',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'varbinary',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'decimal(5,2)',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => 5,
                        'precision' => 5,
                        'scale' => 2,
                        'defaultValue' => 33.22,
                    ],
                    'datetime_col' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'bool_col' => [
                        'type' => 'boolean',
                        'dbType' => 'bit',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'boolean',
                        'dbType' => 'bit',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => true,
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
                        'allowNull' => false,
                        'autoIncrement' => true,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'type' => [
                        'type' => 'string',
                        'dbType' => 'varchar(255)',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => 255,
                        'precision' => 255,
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
