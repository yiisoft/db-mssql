<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
 */
final class BigIntTest extends TestCase
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
        $this->setFixture('Type/bigint.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('bigint_default');

        $this->assertSame('bigint', $tableSchema?->getColumn('Mybigint')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mybigint')->getPhpType());

        $command = $db->createCommand();
        $command->insert('bigint_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybigint' => '9223372036854775807',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `9223372036854775807`, but when the value is greater than `9223372036854775807` it is out of range
     * and save as `9223372036854775807`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/bigint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bigint', ['Mybigint1' => '9223372036854775807', 'Mybigint2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybigint1' => '9223372036854775807',
                'Mybigint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('bigint', ['Mybigint1' => '9223372036854775808', 'Mybigint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybigint1' => '9223372036854775807',
                'Mybigint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    /**
     * Min value is `-9223372036854775808`, but when the value is less than `-9223372036854775808` it is out of range
     * and save as `-9223372036854775808`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/bigint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bigint', ['Mybigint1' => '-9223372036854775808', 'Mybigint2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybigint1' => '-9223372036854775808',
                'Mybigint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('bigint', ['Mybigint1' => '-9223372036854775809', 'Mybigint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybigint1' => '-9223372036854775808',
                'Mybigint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint WHERE id = 2
                SQL
            )->queryOne()
        );
    }
}
