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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/binary-and-varbinary-transact-sql?view=sql-server-ver16
 */
final class BinaryTest extends TestCase
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

        if ($schema->getTableSchema('binary_default') !== null) {
            $command->dropTable('binary_default')->execute();
        }

        $command->createTable(
            'binary_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mybinary1' => 'BINARY(10) DEFAULT CONVERT(binary(10), \'binary\')',
                'Mybinary2' => 'BINARY(1) DEFAULT CONVERT(binary(1), \'b\')',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('binary_default');

        $this->assertSame('binary(10)', $tableSchema?->getColumn('Mybinary1')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mybinary1')->getPhpType());
        $this->assertSame(
            'CONVERT([binary](10),\'binary\')',
            $tableSchema?->getColumn('Mybinary1')->getDefaultValue(),
        );

        $this->assertSame('binary(1)', $tableSchema?->getColumn('Mybinary2')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mybinary2')->getPhpType());
        $this->assertSame('CONVERT([binary](1),\'b\')', $tableSchema?->getColumn('Mybinary2')->getDefaultValue());
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
        $this->setFixture('Type/binary.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('binary_default');

        $this->assertSame('binary(10)', $tableSchema?->getColumn('Mybinary1')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mybinary1')->getPhpType());
        $this->assertSame(
            'CONVERT([binary](10),\'binary\')',
            $tableSchema?->getColumn('Mybinary1')->getDefaultValue(),
        );

        $this->assertSame('binary(1)', $tableSchema?->getColumn('Mybinary2')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mybinary2')->getPhpType());
        $this->assertSame('CONVERT([binary](1),\'b\')', $tableSchema?->getColumn('Mybinary2')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('binary_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybinary1' => '0x62696E61727900000000',
                'Mybinary2' => 'b',
            ],
            $command->setSql(
                <<<SQL
                SELECT id, CONVERT(VARCHAR(100), Mybinary1, 1) AS Mybinary1, Mybinary2 FROM binary_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * When the value is greater than the maximum value, the value is truncated.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/binary.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('binary', [
            'Mybinary1' => new Expression('CONVERT(binary(10), \'binary_default_value\')'),
            'Mybinary3' => new Expression('CONVERT(binary(1), \'bb\')'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybinary1' => '0x62696E6172795F646566',
                'Mybinary2' => null,
                'Mybinary3' => 'b',
                'Mybinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT id, CONVERT(VARCHAR(100), Mybinary1, 1) AS Mybinary1, Mybinary2, Mybinary3, Mybinary4 FROM binary WHERE id = 1
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
        $this->setFixture('Type/binary.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('binary', [
            'Mybinary1' => new Expression('CONVERT(binary(10), \'binary\')'),
            'Mybinary2' => new Expression('CONVERT(binary(10), null)'),
            'Mybinary3' => new Expression('CONVERT(binary(1), \'b\')'),
            'Mybinary4' => new Expression('CONVERT(binary(1), null)'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybinary1' => '0x62696E61727900000000',
                'Mybinary2' => null,
                'Mybinary3' => 'b',
                'Mybinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT id, CONVERT(VARCHAR(100), Mybinary1, 1) AS Mybinary1, Mybinary2, Mybinary3, Mybinary4 FROM binary WHERE id = 1
                SQL
            )->queryOne()
        );
    }
}
