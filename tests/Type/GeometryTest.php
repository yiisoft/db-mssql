<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(): void
    {
        $this->setFixture('Type/geometry.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('geometry_default');

        $this->assertSame('geometry', $tableSchema?->getColumn('Mygeometry1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mygeometry1')->getPhpType());
        $this->assertSame(
            '[geometry]::STGeomFromText(\'POINT(0 0)\',(0))',
            $tableSchema?->getColumn('Mygeometry1')->getDefaultValue(),
        );

        $this->assertSame('nvarchar', $tableSchema?->getColumn('Mygeometry2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mygeometry2')->getPhpType());
        $this->assertNull($tableSchema?->getColumn('Mygeometry2')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('geometry_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mygeometry1' => 'POINT (0 0)',
                'Mygeometry2' => 'POINT (0 0)',
            ],
            $command->setSql(
                <<<SQL
                SELECT id, CAST(Mygeometry1 AS NVARCHAR(MAX)) AS Mygeometry1, Mygeometry2 FROM geometry_default WHERE id = 1
                SQL
            )->queryOne()
        );
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
                SELECT id, CAST(Mygeometry1 AS NVARCHAR(MAX)) AS Mygeometry1, Mygeometry2 FROM geometry WHERE id = 1
                SQL
            )->queryOne()
        );
    }
}
