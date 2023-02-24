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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/decimal-and-numeric-transact-sql?view=sql-server-ver16
 */
final class NumericTest extends TestCase
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
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('numeric_default');

        $this->assertSame('numeric(38,0)', $tableSchema?->getColumn('Mynumeric')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Mynumeric')->getPhpType());

        $command = $db->createCommand();
        $command->insert('numeric_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mynumeric' => '99999999999999997748809823456034029568',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM numeric_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `99999999999999997748809823456034029568`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('99999999999999997748809823456034029568'), 'Mynumeric2' => 0]
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mynumeric1' => '99999999999999997748809823456034029568',
                'Mynumeric2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM numeric WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('99999999999999997748809823456034029568'), 'Mynumeric2' => null]
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mynumeric1' => '99999999999999997748809823456034029568',
                'Mynumeric2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM numeric WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    /**
     * Min value is `-99999999999999997748809823456034029569`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('-99999999999999997748809823456034029569'), 'Mynumeric2' => 0],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mynumeric1' => '-99999999999999997748809823456034029569',
                'Mynumeric2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM numeric WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('-99999999999999997748809823456034029569'), 'Mynumeric2' => null]
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mynumeric1' => '-99999999999999997748809823456034029569',
                'Mynumeric2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM numeric WHERE id = 2
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
    public function testMinValueException(): void
    {
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38)."
        );

        $command->insert(
            'decimal',
            ['Mynumeric1' => new Expression('-199999999999999997748809823456034029570')],
        )->execute();
    }
}
