<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Tests\Provider\BaseSchemaProvider;
use Yiisoft\Db\Tests\Support\AnyValue;

final class SchemaProvider
{
    public function columns(): array
    {
        return [
            [
                [
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'integer',
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
                        'phpType' => 'integer',
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
                        'phpType' => 'integer',
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
                        'phpType' => 'integer',
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
                        'dbType' => 'decimal',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'float',
                        'dbType' => 'float',
                        'phpType' => 'double',
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
                        'phpType' => 'resource',
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
                        'dbType' => 'decimal',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '33.22',
                    ],
                    'time' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'bool_col' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint',
                        'phpType' => 'integer',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => [],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                ],
            ],
        ];
    }

    public function constraints(): array
    {
        $baseSchemaProvider = new BaseSchemaProvider();

        $constraints = $baseSchemaProvider->constraints();

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
}
