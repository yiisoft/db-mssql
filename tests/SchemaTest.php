<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Schema\TableSchemaInterface;
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

    public function testGetViewNames(): void
    {
        $schema = $this->getConnection()->getSchema();

        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->getViewNames());
        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->getViewNames('dbo'));
        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->getViewNames('dbo', true));
    }

    public function testFindUniquesIndex(): void
    {
        $schema = $this->getConnection(true)->getSchema();
        $tUpsert = $schema->getTableSchema('T_upsert');
        $this->assertInstanceOf(TableSchemaInterface::class, $tUpsert);
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
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER]
        )->execute();
        $insertResult = $db->createCommand()->insertEx('testPKTable', ['bar' => 1]);
        $selectResult = $db->createCommand('select [id] from [testPKTable] where [bar]=1')->queryOne();
        $this->assertIsArray($insertResult);
        $this->assertIsArray($selectResult);
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
        $tableSchema = $schema->getTableSchema('type', false);
        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
        $columns = $tableSchema->getColumns();

        $expectedType = null;
        $expectedSize = null;
        $expectedDbType = null;

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

            $db->getPDO()?->setAttribute($name, $value);
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
        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
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

            $db->getPDO()?->setAttribute($name, $value);
        }

        $schema = $db->getSchema();
        $tables = $schema->getTableSchemas();
        $this->assertCount(count($schema->getTableNames()), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchemaInterface::class, $table);
        }
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

        $this->assertNotNull($this->schemaCache);
        $this->schemaCache->setEnable(true);

        $db->setTablePrefix($tablePrefix);
        $noCacheTable = $schema->getTableSchema($tableName, true);
        $this->assertInstanceOf(TableSchemaInterface::class, $noCacheTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $testNoCacheTable = $schema->getTableSchema($testTableName);
        $this->assertSame($noCacheTable, $testNoCacheTable);

        $db->setTablePrefix($tablePrefix);
        $schema->refreshTableSchema($tableName);
        $refreshedTable = $schema->getTableSchema($tableName, false);
        $this->assertInstanceOf(TableSchemaInterface::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $schema->refreshTableSchema($testTablePrefix);
        $testRefreshedTable = $schema->getTableSchema($testTableName, false);
        $this->assertInstanceOf(TableSchemaInterface::class, $testRefreshedTable);
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
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
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
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $constraints = $db->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
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

    /**
     * @dataProvider tableSchemaWithDbSchemesDataProvider
     */
    public function testTableSchemaWithDbSchemes(string $tableName, string $expectedTableName): void
    {
        $db = $this->getConnection();

        $commandMock = $this->createMock(CommandInterface::class);
        $commandMock
            ->method('queryAll')
            ->willReturn([]);

        $mockDb = $this->createMock(ConnectionInterface::class);

        $mockDb->method('getQuoter')
            ->willReturn($db->getQuoter());

        $mockDb
            ->method('createCommand')
            ->with(self::callback(function($sql) {return true;}), self::callback(function ($params) use($expectedTableName) {
                $this->assertEquals($expectedTableName, $params[':fullName']);
                return true;
            }))
            ->willReturn($commandMock);

        $schema = new Schema($mockDb, $this->createSchemaCache());

        $schema->getTablePrimaryKey($tableName);
    }

    public function tableSchemaWithDbSchemesDataProvider(): array
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

    /**
     * @dataProvider quoterTablePartsDataProvider
     */
    public function testQuoterTableParts(string $tableName, ...$expectedParts): void
    {
        $quoter = $this->getConnection()->getQuoter();

        $parts = $quoter->getTableNameParts($tableName);

        $this->assertEquals($expectedParts, array_reverse($parts));
    }

    public function quoterTablePartsDataProvider(): array
    {
        return [
            ['animal', 'animal',],
            ['dbo.animal', 'animal', 'dbo'],
            ['[dbo].[animal]', 'animal', 'dbo'],
            ['[other].[animal2]', 'animal2', 'other'],
            ['other.[animal2]', 'animal2', 'other'],
            ['other.animal2', 'animal2', 'other'],
            ['catalog.other.animal2', 'animal2', 'other', 'catalog'],
            ['server.catalog.other.animal2', 'animal2', 'other', 'catalog', 'server'],
            ['unknown_part.server.catalog.other.animal2', 'animal2', 'other', 'catalog', 'server'],
        ];
    }
}
