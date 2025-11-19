<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/spatial-geometry/spatial-types-geometry-transact-sql?view=sql-server-ver16
 */
final class GeometryTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\GeometryProvider::columns
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        ?Expression $defaultValue,
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('geometry_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('geometry_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], CAST([[Mygeometry1]] AS NVARCHAR(MAX)) AS [[Mygeometry1]], [[Mygeometry2]] FROM [[geometry_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\GeometryProvider::columns
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        ?Expression $defaultValue,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/geometry.sql');

        $tableSchema = $db->getTableSchema('geometry_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/geometry.sql');

        $command = $db->createCommand();
        $command->insert('geometry_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], CAST([[Mygeometry1]] AS NVARCHAR(MAX)) AS [[Mygeometry1]], [[Mygeometry2]] FROM [[geometry_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    public function testValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/geometry.sql');

        $command = $db->createCommand();
        $command->insert(
            'geometry',
            [
                'Mygeometry1' => new Expression('geometry::STGeomFromText(\'LINESTRING(100 100,20 180,180 180)\', 0)'),
            ],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mygeometry1' => 'LINESTRING (100 100, 20 180, 180 180)',
                'Mygeometry2' => 'LINESTRING (100 100, 20 180, 180 180)',
            ],
            $command->setSql(
                <<<SQL
                SELECT [[id]], CAST([[Mygeometry1]] AS NVARCHAR(MAX)) AS [[Mygeometry1]], [[Mygeometry2]] FROM [[geometry]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('geometry')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('geometry_default') !== null) {
            $command->dropTable('geometry_default')->execute();
        }

        $command->createTable(
            'geometry_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mygeometry1' => 'GEOMETRY DEFAULT [geometry]::STGeomFromText(\'POINT(0 0)\',(0))',
                'Mygeometry2' => 'GEOMETRY',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mygeometry1' => 'POINT (0 0)',
            'Mygeometry2' => null,
        ];
    }
}
