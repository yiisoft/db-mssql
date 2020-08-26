<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Pdo\PDO;
use Yiisoft\Db\Mssql\Schema\MssqlColumnSchema;
use Yiisoft\Db\Mssql\Schema\MssqlSchema;
use Yiisoft\Db\Mssql\Schema\MssqlTableSchema;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\TestUtility\AnyValue;
use Yiisoft\Db\TestUtility\TestSchemaTrait;

/**
 * @group mssql
 */
final class MssqlSchemaTest extends TestCase
{
    use TestSchemaTrait;

    protected array $expectedSchemas = [
        'dbo',
    ];

    public function getExpectedColumns(): array
    {
        return [
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
        ];
    }

    public function constraintsProvider(): array
    {
        return [
            '1: primary key' => [
                'T_constraints_1',
                'primaryKey',
                (new Constraint())
                    ->name(AnyValue::getInstance())
                    ->columnNames(['C_id'])
            ],
            '1: check' => [
                'T_constraints_1',
                'checks',
                [
                    (new CheckConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_check'])
                        ->expression('([C_check]<>\'\')')
                ]
            ],
            '1: unique' => [
                'T_constraints_1',
                'uniques',
                [
                    (new Constraint())
                        ->name('CN_unique')
                        ->columnNames(['C_unique'])
                ]
            ],
            '1: index' => [
                'T_constraints_1',
                'indexes',
                [
                    (new IndexConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_id'])
                        ->unique(true)
                        ->primary(true),
                    (new IndexConstraint())
                        ->name('CN_unique')
                        ->columnNames(['C_unique'])
                        ->primary(false)
                        ->unique(true)
                ]
            ],
            '1: default' => [
                'T_constraints_1',
                'defaultValues',
                [
                    (new DefaultValueConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_default'])
                        ->value('((0))')
                ]
            ],

            '2: primary key' => [
                'T_constraints_2',
                'primaryKey',
                (new Constraint())
                ->name('CN_pk')
                ->columnNames(['C_id_1', 'C_id_2'])
            ],
            '2: unique' => [
                'T_constraints_2',
                'uniques',
                [
                    (new Constraint())
                        ->name('CN_constraints_2_multi')
                        ->columnNames(['C_index_2_1', 'C_index_2_2'])
                ]
            ],
            '2: index' => [
                'T_constraints_2',
                'indexes',
                [
                    (new IndexConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_id_1', 'C_id_2'])
                        ->unique(true)
                        ->primary(true),
                    (new IndexConstraint())
                        ->name('CN_constraints_2_single')
                        ->columnNames(['C_index_1'])
                        ->primary(false)
                        ->unique(false),
                    (new IndexConstraint())
                        ->name('CN_constraints_2_multi')
                        ->columnNames(['C_index_2_1', 'C_index_2_2'])
                        ->primary(false)
                        ->unique(true)
                ]
            ],
            '2: check' => ['T_constraints_2', 'checks', []],
            '2: default' => ['T_constraints_2', 'defaultValues', []],

            '3: primary key' => ['T_constraints_3', 'primaryKey', null],
            '3: foreign key' => [
                'T_constraints_3',
                'foreignKeys',
                [
                    (new ForeignKeyConstraint())
                        ->name('CN_constraints_3')
                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
                        ->foreignSchemaName('dbo')
                        ->foreignTableName('T_constraints_2')
                        ->foreignColumnNames(['C_id_1', 'C_id_2'])
                        ->onDelete('CASCADE')
                        ->onUpdate('CASCADE')
                ]
            ],
            '3: unique' => ['T_constraints_3', 'uniques', []],
            '3: index' => ['T_constraints_3', 'indexes', []],
            '3: check' => ['T_constraints_3', 'checks', []],
            '3: default' => ['T_constraints_3', 'defaultValues', []],

            '4: primary key' => [
                'T_constraints_4',
                'primaryKey',
                (new Constraint())
                ->name(AnyValue::getInstance())
                ->columnNames(['C_id'])
            ],
            '4: unique' => [
                'T_constraints_4',
                'uniques',
                [
                    (new Constraint())
                    ->name('CN_constraints_4')
                    ->columnNames(['C_col_1', 'C_col_2'])
                ]
            ],
            '4: check' => ['T_constraints_4', 'checks', []],
            '4: default' => ['T_constraints_4', 'defaultValues', []],
        ];
    }

    public function testGetStringFieldsSize(): void
    {
        /* @var $db Connection */
        $db = $this->getConnection();

        /* @var $schema Schema */
        $schema = $db->getSchema();

        $columns = $schema->getTableSchema('type', false)->getColumns();

        foreach ($columns as $name => $column) {
            $type = $column->getType();
            $size = $column->getSize();
            $dbType = $column->getDbType();

            if (strpos($name, 'char_') === 0) {
                switch ($name) {
                    case 'char_col':
                        $expectedType = 'char';
                        $expectedSize = 100;
                        $expectedDbType = 'char(100)';
                        break;
                    case 'char_col2':
                        $expectedType = 'string';
                        $expectedSize = 100;
                        $expectedDbType = "varchar(100)";
                        break;
                    case 'char_col3':
                        $expectedType = 'text';
                        $expectedSize = null;
                        $expectedDbType = 'text';
                        break;
                }

                $this->assertEquals($expectedType, $type);
                $this->assertEquals($expectedSize, $size);
                $this->assertEquals($expectedDbType, $dbType);
            }
        }
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

    /**
     * @dataProvider quoteTableNameDataProvider
     *
     * @param string $name
     * @param string $expectedName
     */
    public function testQuoteTableName(string $name, string $expectedName)
    {
        $schema = $this->getConnection()->getSchema();
        $quotedName = $schema->quoteTableName($name);

        $this->assertEquals($expectedName, $quotedName);
    }

    public function getTableSchemaDataProvider(): array
    {
        return [
            ['[dbo].[profile]', 'profile'],
            ['dbo.profile', 'profile'],
            ['profile', 'profile'],
            ['dbo.[table.with.special.characters]', 'table.with.special.characters'],
        ];
    }

    /**
     * @dataProvider getTableSchemaDataProvider
     *
     * @param string $name
     * @param string $expectedName
     */
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $schema = $this->getConnection()->getSchema();
        $tableSchema = $schema->getTableSchema($name);

        $this->assertEquals($expectedName, $tableSchema->getName());
    }
}
