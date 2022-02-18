<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\PDO\SchemaPDOMssql;
use Yiisoft\Db\Mssql\TableSchema;
use Yiisoft\Db\TestSupport\AnyValue;
use Yiisoft\Db\TestSupport\TestSchemaTrait;

use function strpos;

/**
 * @group mssql
 */
final class SchemaTest extends TestCase
{
    use TestSchemaTrait;

    protected array $expectedSchemas = [
        'dbo',
    ];

    public function testFindViewNames(): void
    {
        $schema = $this->getConnection()->getSchema();
        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->findViewNames());
        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->findViewNames('dbo'));
    }

    public function testFindUniquesIndex(): void
    {
        $schema = $this->getConnection(true)->getSchema();
        $tUpsert = $schema->getTableSchema('T_upsert');
        $this->assertContains([0 => 'email', 1 => 'recovery_email'], $schema->findUniqueIndexes($tUpsert));
        $this->assertContains([0 => 'email'], $schema->findUniqueIndexes($tUpsert));
    }

    public function testGetPrimaryKey(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('testPKTable') !== null) {
            $db->createCommand()->dropTable('testPKTable')->execute();
        }

        $db->createCommand()->createTable(
            'testPKTable',
            ['id' => SchemaPDOMssql::TYPE_PK, 'bar' => SchemaPDOMssql::TYPE_INTEGER]
        )->execute();
        $insertResult = $db->getSchema()->insert('testPKTable', ['bar' => 1]);
        $selectResult = $db->createCommand('select [id] from [testPKTable] where [bar]=1')->queryOne();
        $this->assertEquals($selectResult['id'], $insertResult['id']);
    }

    public function testGetSchemaNames(): void
    {
        $schemasNames = $this->getConnection()->getSchema()->getSchemaNames();
        $this->assertNotEmpty($schemasNames);

        foreach ($this->expectedSchemas as $schema) {
            $this->assertContains($schema, $schemasNames);
        }
    }

    public function testGetStringFieldsSize(): void
    {
        $schema = $this->getConnection()->getSchema();
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
                        $expectedDbType = 'varchar(100)';
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

    /**
     * @dataProvider pdoAttributesProviderTrait
     *
     * @param array $pdoAttributes
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $db->getPDO()->setAttribute($name, $value);
        }

        $schema = $db->getSchema();
        $tablesNames = $schema->getTableNames();
        $tablesNames = array_map(static fn ($item) => trim($item, '[]'), $tablesNames);
        $this->assertContains('customer', $tablesNames);
        $this->assertContains('category', $tablesNames);
        $this->assertContains('item', $tablesNames);
        $this->assertContains('order', $tablesNames);
        $this->assertContains('order_item', $tablesNames);
        $this->assertContains('type', $tablesNames);
        $this->assertContains('animal', $tablesNames);
        $this->assertContains('animal_view', $tablesNames);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::getTableSchemaDataProvider
     *
     * @param string $name
     * @param string $expectedName
     */
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $tableSchema = $this->getConnection()->getSchema()->getTableSchema($name);
        $this->assertEquals($expectedName, $tableSchema->getName());
    }

    /**
     * @dataProvider pdoAttributesProviderTrait
     *
     * @param array $pdoAttributes
     */
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $db->getPDO()->setAttribute($name, $value);
        }

        $schema = $db->getSchema();
        $tables = $schema->getTableSchemas();
        $this->assertCount(count($schema->getTableNames()), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchema::class, $table);
        }
    }

    public function testGetViewNames(): void
    {
        $schema = $this->getConnection()->getSchema();
        $this->assertSame(
            ['dbo' => [0 => '[animal_view]', 1 => '[testCreateView]']],
            $schema->getViewNames('dbo', true),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::quoteTableNameDataProvider
     *
     * @param string $name
     * @param string $expectedName
     */
    public function testQuoteTableName(string $name, string $expectedName): void
    {
        $db = $this->getConnection();
        $quotedName = $db->getQuoter()->quoteTableName($name);
        $this->assertEquals($expectedName, $quotedName);
    }

    /**
     * @depends testSchemaCache
     *
     * @dataProvider tableSchemaCachePrefixesProviderTrait
     *
     * @param string $tablePrefix
     * @param string $tableName
     * @param string $testTablePrefix
     * @param string $testTableName
     */
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName
    ): void {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        $this->schemaCache->setEnable(true);

        $db->setTablePrefix($tablePrefix);
        $noCacheTable = $schema->getTableSchema($tableName, true);
        $this->assertInstanceOf(TableSchema::class, $noCacheTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $testNoCacheTable = $schema->getTableSchema($testTableName);
        $this->assertSame($noCacheTable, $testNoCacheTable);

        $db->setTablePrefix($tablePrefix);
        $schema->refreshTableSchema($tableName);
        $refreshedTable = $schema->getTableSchema($tableName, false);
        $this->assertInstanceOf(TableSchema::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $schema->refreshTableSchema($testTablePrefix);
        $testRefreshedTable = $schema->getTableSchema($testTableName, false);
        $this->assertInstanceOf(TableSchema::class, $testRefreshedTable);
        $this->assertEquals($refreshedTable, $testRefreshedTable);
        $this->assertNotSame($testNoCacheTable, $testRefreshedTable);
    }

    public function constraintsProvider()
    {
        $result = $this->constraintsProviderTrait();
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

        $constraints = $this->getConnection()->getSchema()->{'getTable' . ucfirst($type)}($tableName);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider lowercaseConstraintsProviderTrait
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

        $db = $this->getConnection();
        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $constraints = $db->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider uppercaseConstraintsProviderTrait
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

        $db = $this->getConnection();
        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $constraints = $db->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
    }

    public function testReleaseSavepoint(): void
    {
        $schema = $this->getConnection()->getSchema();
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mssql\PDO\SchemaPDOMssql::releaseSavepoint is not supported.');
        $schema->releaseSavepoint('savepoint');
    }

    protected function getExpectedColumns(): array
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
}
