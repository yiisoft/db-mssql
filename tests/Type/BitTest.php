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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/bit-transact-sql?view=sql-server-ver16
 */
final class BitTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableDefaultValue(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('bit_default') !== null) {
            $command->dropTable('bit_default')->execute();
        }

        $command->createTable(
            'bit_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mybit1' => 'BIT DEFAULT 0', // Min value
                'Mybit2' => 'BIT DEFAULT 1', // Max value
                'Mybit3' => 'BIT DEFAULT 2', // Max value
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('bit_default');

        $this->assertSame('bit', $tableSchema?->getColumn('Mybit1')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mybit1')->getPhpType());
        $this->assertSame(0, $tableSchema?->getColumn('Mybit1')->getDefaultValue());

        $this->assertSame('bit', $tableSchema?->getColumn('Mybit2')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mybit2')->getPhpType());
        $this->assertSame(1, $tableSchema?->getColumn('Mybit2')->getDefaultValue());

        $this->assertSame('bit', $tableSchema?->getColumn('Mybit3')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mybit3')->getPhpType());
        $this->assertSame(2, $tableSchema?->getColumn('Mybit3')->getDefaultValue());
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
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('bit_default');

        $this->assertSame('bit', $tableSchema->getColumn('Mybit1')->getDbType());
        $this->assertSame('integer', $tableSchema->getColumn('Mybit1')->getPhpType());
        $this->assertSame(0, $tableSchema->getColumn('Mybit1')->getDefaultValue());

        $this->assertSame('bit', $tableSchema->getColumn('Mybit2')->getDbType());
        $this->assertSame('integer', $tableSchema->getColumn('Mybit2')->getPhpType());
        $this->assertSame(1, $tableSchema->getColumn('Mybit2')->getDefaultValue());

        $this->assertSame('bit', $tableSchema->getColumn('Mybit3')->getDbType());
        $this->assertSame('integer', $tableSchema->getColumn('Mybit3')->getPhpType());
        $this->assertSame(2, $tableSchema->getColumn('Mybit3')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('bit_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '0',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit_default WHERE id = 1
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
    public function testBoolean(): void
    {
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => true, 'Mybit2' => false, 'Mybit3' => true])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '1',
                'Mybit2' => '0',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `1`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => 1, 'Mybit2' => 1, 'Mybit3' => 1])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '1',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('bit', ['Mybit1' => 0.5, 'Mybit2' => 3, 'Mybit3' => 4])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybit1' => '0',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    /**
     * Min value is `0`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     *
     * @https://learn.microsoft.com/en-us/sql/t-sql/data-types/bit-transact-sql?view=sql-server-ver16#remarks
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => 0, 'Mybit2' => 0, 'Mybit3' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '0',
                'Mybit2' => '0',
                'Mybit3' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('bit', ['Mybit1' => null, 'Mybit2' => 0.8, 'Mybit3' => -3])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybit1' => null,
                'Mybit2' => '0',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 2
                SQL
            )->queryOne()
        );
    }
}
