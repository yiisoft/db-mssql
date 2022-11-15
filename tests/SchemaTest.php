<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;

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
    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $subQuery = $this->getQuery($db)->select('bar')->from('testCreateViewTable')->where(['>', 'bar', '5']);

        if ($db->getSchema()->getTableSchema('testCreateView') !== null) {
            $command->dropView('testCreateView')->execute();
        }

        if ($db->getSchema()->getTableSchema('testCreateViewTable')) {
            $command->dropTable('testCreateViewTable')->execute();
        }

        $command
            ->createTable('testCreateViewTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER])->execute();
        $command->insert('testCreateViewTable', ['bar' => 1])->execute();
        $command->insert('testCreateViewTable', ['bar' => 6])->execute();
        $command->createView('testCreateView', $subQuery)->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[bar]] FROM {{testCreateView}};
            SQL
        )->queryAll();

        $this->assertEquals([['bar' => 6]], $records);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame('dbo', $schema->getDefaultSchema());
    }

    /**
     * @throws NotSupportedException
     * @throws Exception
     */
    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection();

        $expectedSchemas = ['dbo'];
        $schema = $db->getSchema();
        $schemasNames = $schema->getSchemaNames();

        $this->assertNotEmpty($schemasNames);

        foreach ($expectedSchemas as $schema) {
            $this->assertContains($schema, $schemasNames);
        }
    }

    /**
     * @throws Exception
     *
     * @depends testCreateView
     */
    public function testGetViewNames(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $this->assertSame([0 => '[animal_view]', 1 => 'testCreateView'], $schema->getViewNames());
        $this->assertSame([0 => '[animal_view]', 1 => 'testCreateView'], $schema->getViewNames('dbo'));
        $this->assertSame([0 => '[animal_view]', 1 => 'testCreateView'], $schema->getViewNames('dbo', true));
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
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }
}
