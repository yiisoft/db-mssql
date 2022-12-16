<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends CommonSchemaTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::columns()
     */
    public function testColumnSchema(array $columns): void
    {
        parent::testColumnSchema($columns);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testFindUniquesIndexes(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tUpsert = $schema->getTableSchema('T_upsert');

        $this->assertInstanceOf(TableSchemaInterface::class, $tUpsert);
        $this->assertContains([0 => 'email', 1 => 'recovery_email'], $schema->findUniqueIndexes($tUpsert));
    }

    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame('dbo', $schema->getDefaultSchema());
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $schemas = $schema->getSchemaNames();

        $this->assertSame(['dbo', 'guest'], $schemas);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::columnsTypeChar()
     */
    public function testGetStringFieldsSize(
        string $columnName,
        string $columnType,
        int|null $columnSize,
        string $columnDbType
    ): void {
        parent::testGetStringFieldsSize($columnName, $columnType, $columnSize, $columnDbType);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::pdoAttributes()
     *
     * @throws NotSupportedException
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

        $this->assertContains('[customer]', $tablesNames);
        $this->assertContains('[category]', $tablesNames);
        $this->assertContains('[item]', $tablesNames);
        $this->assertContains('[order]', $tablesNames);
        $this->assertContains('[order_item]', $tablesNames);
        $this->assertContains('[type]', $tablesNames);
        $this->assertContains('[animal]', $tablesNames);
        $this->assertContains('[animal_view]', $tablesNames);
    }

    public function testGetViewNames(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();

        $this->assertSame(['[animal_view]'], $schema->getViewNames());
        $this->assertSame(['[animal_view]'], $schema->getViewNames('dbo'));
        $this->assertSame(['[animal_view]'], $schema->getViewNames('dbo', true));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraints($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::tableSchemaWithDbSchemes()
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
            ->with(
                self::callback(fn ($sql) => true),
                self::callback(
                    function ($params) use ($expectedTableName) {
                        $this->assertEquals($expectedTableName, $params[':fullName']);

                        return true;
                    }
                )
            )
            ->willReturn($commandMock);

        $schema = new Schema($mockDb, DbHelper::getSchemaCache());
        $schema->getTablePrimaryKey($tableName);
    }
}
