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
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/float-and-real-transact-sql?view=sql-server-ver16
 */
final class RealTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildtable();

        $tableSchema = $db->getTableSchema('real_default');

        $this->assertSame('real', $tableSchema?->getColumn('Myreal')->getDbType());
        $this->assertSame('float', $tableSchema?->getColumn('Myreal')->getPhpType());
        $this->assertSame(3.4000000000000000e+038, $tableSchema?->getColumn('Myreal')->getDefaultValue());

        $db->createCommand()->dropTable('real_default')->execute();
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
        $command->insert('real_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[real_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('real_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(): void
    {
        $this->setFixture('Type/real.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('real_default');

        $this->assertSame('real', $tableSchema?->getColumn('Myreal')->getDbType());
        $this->assertSame('float', $tableSchema?->getColumn('Myreal')->getPhpType());
        $this->assertSame(3.4000000000000000e+038, $tableSchema?->getColumn('Myreal')->getDefaultValue());

        $db->createCommand()->dropTable('real_default')->execute();
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
        $this->setFixture('Type/real.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('real_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[real_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('real_default')->execute();
    }

    /**
     * Max value is `3.4E+38`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/real.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('real', ['Myreal1' => '3.4E+38', 'Myreal2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myreal1' => '3.4E+38',
                'Myreal2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('real', ['Myreal1' => '3.4E+38', 'Myreal2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myreal1' => '3.4E+38',
                'Myreal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('real')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValueException(): void
    {
        $this->setFixture('Type/real.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error for type real'
        );

        $command->insert('real', ['Myreal1' => new Expression('4.4E+38')])->execute();
    }

    /**
     * Min value is `-3.40E+38`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/real.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('real', ['Myreal1' => '-3.4E+38', 'Myreal2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myreal1' => '-3.4E+38',
                'Myreal2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('real', ['Myreal1' => '-3.4E+38', 'Myreal2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myreal1' => '-3.4E+38',
                'Myreal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('real')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValueException(): void
    {
        $this->setFixture('Type/real.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error for type real'
        );

        $command->insert('real', ['Myreal1' => new Expression('-4.4E+38')])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('real_default') !== null) {
            $command->dropTable('real_default')->execute();
        }

        $command->createTable(
            'real_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myreal' => 'REAL DEFAULT 3.4000000000000000e+038',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myreal' => '3.4E+38',
        ];
    }
}
