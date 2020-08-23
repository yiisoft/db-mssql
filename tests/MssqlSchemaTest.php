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

final class MssqlSchemaTest extends TestCase
{
    protected array $expectedSchemas = [
        'dbo',
    ];

    public function pdoAttributesProvider(): array
    {
        return [
            [[PDO::ATTR_EMULATE_PREPARES => true]],
            [[PDO::ATTR_EMULATE_PREPARES => false]],
        ];
    }

    public function testGetSchemaNames(): void
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $schemas = $schema->getSchemaNames();

        $this->assertNotEmpty($schemas);

        foreach ($this->expectedSchemas as $schema) {
            $this->assertContains($schema, $schemas);
        }
    }

    /**
     * @dataProvider pdoAttributesProvider
     *
     * @param array $pdoAttributes
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $connection = $this->getConnection();

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $connection->getPDO()->setAttribute($name, $value);
        }

        /* @var $schema Schema */
        $schema = $connection->getSchema();

        $tables = $schema->getTableNames();
        $tables = array_map(static function ($item) {
            return trim($item, '[]');
        }, $tables);

        $this->assertTrue(in_array('customer', $tables));
        $this->assertTrue(in_array('category', $tables));
        $this->assertTrue(in_array('item', $tables));
        $this->assertTrue(in_array('order', $tables));
        $this->assertTrue(in_array('order_item', $tables));
        $this->assertTrue(in_array('type', $tables));
        $this->assertTrue(in_array('animal', $tables));
        $this->assertTrue(in_array('animal_view', $tables));
    }

    /**
     * @dataProvider pdoAttributesProvider
     *
     * @param array $pdoAttributes
     */
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $connection = $this->getConnection();

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $connection->getPDO()->setAttribute($name, $value);
        }

        /* @var $schema Schema */
        $schema = $connection->getSchema();

        $tables = $schema->getTableSchemas();

        $this->assertEquals(count($schema->getTableNames()), count($tables));

        foreach ($tables as $table) {
            $this->assertInstanceOf(MssqlTableSchema::class, $table);
        }
    }

    public function testGetTableSchemasWithAttrCase(): void
    {
        $db = $this->getConnection(false);

        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $this->assertEquals(count($db->getSchema()->getTableNames()), count($db->getSchema()->getTableSchemas()));

        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $this->assertEquals(count($db->getSchema()->getTableNames()), count($db->getSchema()->getTableSchemas()));
    }

    public function testGetNonExistingTableSchema(): void
    {
        $this->assertNull($this->getConnection()->getSchema()->getTableSchema('nonexisting_table'));
    }

    public function testSchemaCache(): void
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $schema->getDb()->setEnableSchemaCache(true);
        $schema->getDb()->setSchemaCache($this->cache);

        $noCacheTable = $schema->getTableSchema('type', true);
        $cachedTable = $schema->getTableSchema('type', false);

        $this->assertEquals($noCacheTable, $cachedTable);
    }

    /**
     * @depends testSchemaCache
     */
    public function testRefreshTableSchema(): void
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $schema->getDb()->setEnableSchemaCache(true);

        $schema->getDb()->setSchemaCache($this->cache);
        $noCacheTable = $schema->getTableSchema('type', true);

        $schema->refreshTableSchema('type');
        $refreshedTable = $schema->getTableSchema('type', false);
        $this->assertNotSame($noCacheTable, $refreshedTable);
    }

    public function tableSchemaCachePrefixesProvider(): array
    {
        $configs = [
            [
                'prefix' => '',
                'name' => 'type',
            ],
            [
                'prefix' => '',
                'name' => '{{%type}}',
            ],
            [
                'prefix' => 'ty',
                'name' => '{{%pe}}',
            ],
        ];

        $data = [];
        foreach ($configs as $config) {
            foreach ($configs as $testConfig) {
                if ($config === $testConfig) {
                    continue;
                }

                $description = sprintf(
                    "%s (with '%s' prefix) against %s (with '%s' prefix)",
                    $config['name'],
                    $config['prefix'],
                    $testConfig['name'],
                    $testConfig['prefix']
                );
                $data[$description] = [
                    $config['prefix'],
                    $config['name'],
                    $testConfig['prefix'],
                    $testConfig['name'],
                ];
            }
        }

        return $data;
    }

    /**
     * @dataProvider tableSchemaCachePrefixesProvider
     *
     * @depends testSchemaCache
     */
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName
    ): void {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();
        $schema->getDb()->setEnableSchemaCache(true);

        $schema->getDb()->setTablePrefix($tablePrefix);
        $schema->getDb()->setSchemaCache($this->cache);
        $noCacheTable = $schema->getTableSchema($tableName, true);
        $this->assertInstanceOf(MssqlTableSchema::class, $noCacheTable);

        // Compare
        $schema->getDb()->setTablePrefix($testTablePrefix);
        $testNoCacheTable = $schema->getTableSchema($testTableName);
        $this->assertSame($noCacheTable, $testNoCacheTable);

        $schema->getDb()->setTablePrefix($testTablePrefix);
        $schema->refreshTableSchema($tableName);
        $refreshedTable = $schema->getTableSchema($tableName, false);
        //$this->assertInstanceOf(MssqlTableSchema::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        // Compare
        $schema->getDb()->setTablePrefix($testTablePrefix);
        $schema->refreshTableSchema($testTablePrefix);
        $testRefreshedTable = $schema->getTableSchema($testTableName, false);

        $this->assertInstanceOf(MssqlTableSchema::class, $testRefreshedTable);
        //$this->assertEquals($refreshedTable, $testRefreshedTable);
        //$this->assertNotSame($testNoCacheTable, $testRefreshedTable);
    }

    public function testCompositeFk(): void
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('composite_fk');

        $fk = $table->getForeignKeys();

        $this->assertCount(1, $fk);
        $this->assertTrue(isset($fk['FK_composite_fk_order_item']));
        $this->assertEquals('order_item', $fk['FK_composite_fk_order_item'][0]);
        $this->assertEquals('order_id', $fk['FK_composite_fk_order_item']['order_id']);
        $this->assertEquals('item_id', $fk['FK_composite_fk_order_item']['item_id']);
    }

    public function testGetPDOType(): void
    {
        $values = [
            [null, PDO::PARAM_NULL],
            ['', PDO::PARAM_STR],
            ['hello', PDO::PARAM_STR],
            [0, PDO::PARAM_INT],
            [1, PDO::PARAM_INT],
            [1337, PDO::PARAM_INT],
            [true, PDO::PARAM_BOOL],
            [false, PDO::PARAM_BOOL],
            [$fp = fopen(__FILE__, 'rb'), PDO::PARAM_LOB],
        ];

        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        foreach ($values as $value) {
            $this->assertEquals(
                $value[1],
                $schema->getPdoType($value[0]),
                'type for value ' . print_r($value[0], true) . ' does not match.'
            );
        }

        fclose($fp);
    }

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

    public function testColumnSchema(): void
    {
        $columns = $this->getExpectedColumns();

        $table = $this->getConnection(false)->getSchema()->getTableSchema('type', true);

        $expectedColNames = array_keys($columns);
        sort($expectedColNames);

        $colNames = $table->getColumnNames();
        sort($colNames);

        $this->assertEquals($expectedColNames, $colNames);

        foreach ($table->getColumns() as $name => $column) {
            $expected = $columns[$name];

            $this->assertSame(
                $expected['dbType'],
                $column->getDbType(),
                "dbType of column $name does not match. type is {$column->getType()}, dbType is {$column->getType()}."
            );
            $this->assertSame(
                $expected['phpType'],
                $column->getPhpType(),
                "phpType of column $name does not match. type is {$column->getType()},
                dbType is {$column->getDbType()}."
            );
            $this->assertSame(
                $expected['type'],
                $column->getType(),
                "type of column $name does not match."
            );
            $this->assertSame(
                $expected['allowNull'],
                $column->isAllowNull(),
                "allowNull of column $name does not match."
            );
            $this->assertSame(
                $expected['autoIncrement'],
                $column->isAutoIncrement(),
                "autoIncrement of column $name does not match."
            );
            $this->assertSame(
                $expected['enumValues'],
                $column->getEnumValues(),
                "enumValues of column $name does not match."
            );
            $this->assertSame(
                $expected['size'],
                $column->getSize(),
                "size of column $name does not match."
            );
            $this->assertSame(
                $expected['precision'],
                $column->getPrecision(),
                "precision of column $name does not match."
            );
            $this->assertSame(
                $expected['scale'],
                $column->getScale(),
                "scale of column $name does not match."
            );
            if (\is_object($expected['defaultValue'])) {
                $this->assertInternalType(
                    'object',
                    $column->getDefaultValue(),
                    "defaultValue of column $name is expected to be an object but it is not."
                );
                $this->assertEquals(
                    (string) $expected['defaultValue'],
                    (string) $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            } else {
                $this->assertEquals(
                    $expected['defaultValue'],
                    $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            }
        }
    }

    public function testColumnSchemaDbTypecastWithEmptyCharType(): void
    {
        $columnSchema = new MssqlColumnSchema();

        $columnSchema->type(MssqlSchema::TYPE_CHAR);
        $this->assertSame('', $columnSchema->dbTypecast(''));
    }

    public function testNegativeDefaultValues(): void
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('negative_default_values');

        $this->assertEquals(-123, $table->getColumn('tinyint_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('smallint_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('int_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('bigint_col')->getDefaultValue());
        $this->assertEquals(-12345.6789, $table->getColumn('float_col')->getDefaultValue());
        $this->assertEquals(-33.22, $table->getColumn('numeric_col')->getDefaultValue());
    }

    public function testContraintTablesExistance(): void
    {
        $tableNames = [
            'T_constraints_1',
            'T_constraints_2',
            'T_constraints_3',
            'T_constraints_4',
        ];
        $schema = $this->getConnection()->getSchema();
        foreach ($tableNames as $tableName) {
            $tableSchema = $schema->getTableSchema($tableName);
            $this->assertInstanceOf(MssqlTableSchema::class, $tableSchema, $tableName);
        }
    }

    public function constraintsProvider(): array
    {
        $result = [
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
                        ->expression("C_check <> ''")
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
            '1: default' => ['T_constraints_1', 'defaultValues', false],

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
            '2: default' => ['T_constraints_2', 'defaultValues', false],

            '3: primary key' => ['T_constraints_3', 'primaryKey', null],
            '3: foreign key' => [
                'T_constraints_3',
                'foreignKeys',
                [
                    (new ForeignKeyConstraint())
                        ->name('CN_constraints_3')
                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
                        ->foreignTableName('T_constraints_2')
                        ->foreignColumnNames(['C_id_1', 'C_id_2'])
                        ->onDelete('CASCADE')
                        ->onUpdate('CASCADE')
                ]
            ],
            '3: unique' => ['T_constraints_3', 'uniques', []],
            '3: index' => [
                'T_constraints_3',
                'indexes',
                [
                    (new IndexConstraint())
                        ->name('CN_constraints_3')
                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
                        ->unique(false)
                        ->primary(false)
                ]
            ],
            '3: check' => ['T_constraints_3', 'checks', []],
            '3: default' => ['T_constraints_3', 'defaultValues', false],

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
            '4: default' => ['T_constraints_4', 'defaultValues', false],
        ];

        $result['1: check'][2][0]->expression('([C_check]<>\'\')');
        $result['1: default'][2] = [];
        $result['1: default'][2][] = (new DefaultValueConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_default'])
            ->value('((0))');

        $result['2: default'][2] = [];

        $result['3: foreign key'][2][0]->foreignSchemaName('dbo');
        $result['3: index'][2] = [];
        $result['3: default'][2] = [];

        $result['4: default'][2] = [];

        return $result;
    }

    public function lowercaseConstraintsProvider(): array
    {
        return $this->constraintsProvider();
    }

    public function uppercaseConstraintsProvider(): array
    {
        return $this->constraintsProvider();
    }

    /**
     * @dataProvider constraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraints(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $constraints = $this->getConnection(false)->getSchema()->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider uppercaseConstraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection(false);
        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider lowercaseConstraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection(false);
        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    private function assertMetadataEquals($expected, $actual): void
    {
        switch (strtolower(\gettype($expected))) {
            case 'object':
                $this->assertIsObject($actual);
                break;
            case 'array':
                $this->assertIsArray($actual);
                break;
            case 'null':
                $this->assertNull($actual);
                break;
        }

        if (\is_array($expected)) {
            $this->normalizeArrayKeys($expected, false);
            $this->normalizeArrayKeys($actual, false);
        }

        $this->normalizeConstraints($expected, $actual);

        if (\is_array($expected)) {
            $this->normalizeArrayKeys($expected, true);
            $this->normalizeArrayKeys($actual, true);
        }

        $this->assertEquals($expected, $actual);
    }

    private function normalizeArrayKeys(array &$array, $caseSensitive): void
    {
        $newArray = [];

        foreach ($array as $value) {
            if ($value instanceof Constraint) {
                $key = (array) $value;
                unset(
                    $key["\000Yiisoft\Db\Constraint\Constraint\000name"],
                    $key["\u0000Yiisoft\\Db\\Constraint\\ForeignKeyConstraint\u0000foreignSchemaName"]
                );

                foreach ($key as $keyName => $keyValue) {
                    if ($keyValue instanceof AnyCaseValue) {
                        $key[$keyName] = $keyValue->value;
                    } elseif ($keyValue instanceof AnyValue) {
                        $key[$keyName] = '[AnyValue]';
                    }
                }

                ksort($key, SORT_STRING);
                $newArray[$caseSensitive ? json_encode($key) : strtolower(json_encode($key))] = $value;
            } else {
                $newArray[] = $value;
            }
        }

        ksort($newArray, SORT_STRING);

        $array = $newArray;
    }

    private function normalizeConstraints(&$expected, &$actual): void
    {
        if (\is_array($expected)) {
            foreach ($expected as $key => $value) {
                if (!$value instanceof Constraint || !isset($actual[$key]) || !$actual[$key] instanceof Constraint) {
                    continue;
                }

                $this->normalizeConstraintPair($value, $actual[$key]);
            }
        } elseif ($expected instanceof Constraint && $actual instanceof Constraint) {
            $this->normalizeConstraintPair($expected, $actual);
        }
    }

    private function normalizeConstraintPair(Constraint $expectedConstraint, Constraint $actualConstraint): void
    {
        if (get_class($expectedConstraint) !== get_class($actualConstraint)) {
            return;
        }

        foreach (array_keys((array) $expectedConstraint) as $name) {
            if ($expectedConstraint->getName() instanceof AnyValue) {
                $actualConstraint->name($expectedConstraint->getName());
            } elseif ($expectedConstraint->getName() instanceof AnyCaseValue) {
                $actualConstraint->name(new AnyCaseValue($actualConstraint->getName()));
            }
        }
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
