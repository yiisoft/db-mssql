<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\AbstractSchemaTest;

/**
 * @group mssql
 */
final class SchemaTest extends AbstractSchemaTest
{
    use TestTrait;

    private array $expectedSchemas = ['dbo'];

    public function testFindUniquesIndex(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();
        $tUpsert = $schema->getTableSchema('T_upsert');

        $this->assertInstanceOf(TableSchemaInterface::class, $tUpsert);
        $this->assertContains([0 => 'email', 1 => 'recovery_email'], $schema->findUniqueIndexes($tUpsert));
        $this->assertContains([0 => 'email'], $schema->findUniqueIndexes($tUpsert));
    }

    public function testGetPrimaryKey(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('testPKTable') !== null) {
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
        $this->assertSame($selectResult['id'], $insertResult['id']);
    }

    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();
        $schemasNames = $schema->getSchemaNames();

        $this->assertNotEmpty($schemasNames);

        foreach ($this->expectedSchemas as $schema) {
            $this->assertContains($schema, $schemasNames);
        }
    }

    public function testGetStringFieldsSize(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();
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

            if (str_starts_with($name, 'char_')) {
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

                $this->assertSame($expectedType, $type);
                $this->assertSame($expectedSize, $size);
                $this->assertSame($expectedDbType, $dbType);
            }
        }
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\PDOProvider::attributes
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $db = $this->getConnection();

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
     */
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema($name);

        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
        $this->assertSame($expectedName, $tableSchema->getName());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\PDOProvider::attributes
     */
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnection();

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

    public function testGetViewNames(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->getViewNames());
        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->getViewNames('dbo'));
        $this->assertSame([0 => '[animal_view]', 1 => '[testCreateView]'], $schema->getViewNames('dbo', true));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ConstraintProvider::tableConstraints
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection();
        $schema = $db->getSchema();
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ConstraintProvider::tableConstraints
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $schema = $db->getSchema();
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ConstraintProvider::tableConstraints
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $constraints = $db->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::tableSchemaWithDbSchemesDataProvider
     */
    public function testTableSchemaWithDbSchemes(string $tableName, string $expectedTableName): void
    {
        $db = $this->getConnection();

        $commandMock = $this->createMock(CommandInterface::class);
        $commandMock->method('queryAll')->willReturn([]);
        $mockDb = $this->createMock(ConnectionInterface::class);
        $mockDb->method('getQuoter')->willReturn($db->getQuoter());
        $mockDb
            ->method('createCommand')
            ->with(self::callback(fn ($sql) => true), self::callback(function ($params) use ($expectedTableName) {
                $this->assertEquals($expectedTableName, $params[':fullName']);
                return true;
            }))
            ->willReturn($commandMock);

        $schema = new Schema($mockDb, $this->getSchemaCache());

        $schema->getTablePrimaryKey($tableName);
    }
}
