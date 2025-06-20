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
final class FloatTest extends TestCase
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
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('float_default');

        $this->assertSame('float', $tableSchema?->getColumn('Myfloat')->getDbType());
        $this->assertSame('float', $tableSchema?->getColumn('Myfloat')->getPhpType());
        $this->assertSame(2.2300000000000001e-308, $tableSchema?->getColumn('Myfloat')->getDefaultValue());

        $db->createCommand()->insert('float_default', [])->execute();
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
        $command->insert('float_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[float_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('float_default')->execute();
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
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('float_default');

        $this->assertSame('float', $tableSchema?->getColumn('Myfloat')->getDbType());
        $this->assertSame('float', $tableSchema?->getColumn('Myfloat')->getPhpType());
        $this->assertSame(2.2300000000000001e-308, $tableSchema?->getColumn('Myfloat')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('float_default', [])->execute();

        $db->createCommand()->insert('float_default', [])->execute();
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
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('float_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[float_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('float_default')->execute();
    }

    /**
     * Max value is `1.79E+308`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('float', ['Myfloat1' => '1.79E+308', 'Myfloat2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myfloat1' => '1.79E+308',
                'Myfloat2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('float', ['Myfloat1' => '1.79E+308', 'Myfloat2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myfloat1' => '1.79E+308',
                'Myfloat2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('float')->execute();
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
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "[SQL Server]The floating point value '1.80E+308' is out of the range of computer representation (8 bytes)."
        );

        $command->insert('float', ['Myfloat1' => new Expression('1.80E+308')])->execute();
    }

    /**
     * Min value is `-1.79E+308`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('float', ['Myfloat1' => '-1.79E+308', 'Myfloat2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myfloat1' => '-1.79E+308',
                'Myfloat2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('float', ['Myfloat1' => '-1.79E+308', 'Myfloat2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myfloat1' => '-1.79E+308',
                'Myfloat2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('float')->execute();
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
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "[SQL Server]The floating point value '1.80E+308' is out of the range of computer representation (8 bytes)."
        );

        $command->insert('float', ['Myfloat1' => new Expression('-1.80E+308')])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('float_default') !== null) {
            $command->dropTable('float_default')->execute();
        }

        $command->createTable(
            'float_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myfloat' => 'FLOAT DEFAULT 2.2300000000000001e-308',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myfloat' => '2.2300000000000001E-308',
        ];
    }
}
