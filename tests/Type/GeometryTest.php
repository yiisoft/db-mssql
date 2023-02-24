<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/spatial-geometry/spatial-types-geometry-transact-sql?view=sql-server-ver16
 */
final class GeometryTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\GeometryProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        string|null $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('geometry_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
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
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\GeometryProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        string|null $defaultValue
    ): void {
        $this->setFixture('Type/geometry.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('geometry_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValueWithInsert(): void
    {
        $this->setFixture('Type/geometry.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('geometry_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], CAST([[Mygeometry1]] AS NVARCHAR(MAX)) AS [[Mygeometry1]], [[Mygeometry2]] FROM [[geometry_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('geometry_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testValue(): void
    {
        $this->setFixture('Type/geometry.sql');

        $db = $this->getConnection(true);
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
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('geometry')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

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
