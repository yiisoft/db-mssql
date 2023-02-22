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

use function str_repeat;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/binary-and-varbinary-transact-sql?view=sql-server-ver16
 */
final class VarBinaryTest extends TestCase
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

        if ($schema->getTableSchema('varbinary_default') !== null) {
            $command->dropTable('varbinary_default')->execute();
        }

        $command->createTable(
            'varbinary_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myvarbinary1' => 'VARBINARY(10) DEFAULT CONVERT(varbinary(10), \'varbinary\')',
                'Myvarbinary2' => 'VARBINARY(100) DEFAULT CONVERT(varbinary(100), \'v\')',
                'Myvarbinary3' => 'VARBINARY(20) DEFAULT hashbytes(\'MD5\',\'test string\')',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('varbinary_default');

        $this->assertSame('varbinary(10)', $tableSchema?->getColumn('Myvarbinary1')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myvarbinary1')->getPhpType());
        $this->assertSame(
            'CONVERT([varbinary](10),\'varbinary\')',
            $tableSchema?->getColumn('Myvarbinary1')->getDefaultValue(),
        );

        $this->assertSame('varbinary(100)', $tableSchema?->getColumn('Myvarbinary2')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myvarbinary2')->getPhpType());
        $this->assertSame(
            'CONVERT([varbinary](100),\'v\')',
            $tableSchema?->getColumn('Myvarbinary2')->getDefaultValue(),
        );

        $this->assertSame('varbinary(20)', $tableSchema?->getColumn('Myvarbinary3')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myvarbinary3')->getPhpType());
        $this->assertSame(
            'hashbytes(\'MD5\',\'test string\')',
            $tableSchema?->getColumn('Myvarbinary3')->getDefaultValue(),
        );
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
        $this->setFixture('Type/varbinary.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('varbinary_default');

        $this->assertSame('varbinary(10)', $tableSchema?->getColumn('Myvarbinary1')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myvarbinary1')->getPhpType());
        $this->assertSame(
            'CONVERT([varbinary](10),\'varbinary\')',
            $tableSchema?->getColumn('Myvarbinary1')->getDefaultValue(),
        );

        $this->assertSame('varbinary(100)', $tableSchema?->getColumn('Myvarbinary2')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myvarbinary2')->getPhpType());
        $this->assertSame(
            'CONVERT([varbinary](100),\'v\')',
            $tableSchema?->getColumn('Myvarbinary2')->getDefaultValue(),
        );

        $this->assertSame('varbinary(20)', $tableSchema?->getColumn('Myvarbinary3')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myvarbinary3')->getPhpType());
        $this->assertSame(
            'hashbytes(\'MD5\',\'test string\')',
            $tableSchema?->getColumn('Myvarbinary3')->getDefaultValue(),
        );

        $command = $db->createCommand();
        $command->insert('varbinary_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarbinary1' => 'varbinary',
                'Myvarbinary2' => 'v',
                'Myvarbinary3' => '0x6F8DB599DE986FAB7A',
            ],
            $command->setSql(
                <<<SQL
                SELECT id, Myvarbinary1, Myvarbinary2, CONVERT(VARCHAR(20), Myvarbinary3, 1) AS Myvarbinary3 FROM varbinary_default WHERE id = 1
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
        $this->setFixture('Type/varbinary.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('varbinary', [
            'Myvarbinary1' => new Expression('CONVERT(varbinary(10), \'binary_default_value\')'),
            'Myvarbinary3' => new Expression('CONVERT(binary(100), \'' . str_repeat('v', 101) . '\')'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarbinary1' => 'binary_def',
                'Myvarbinary2' => null,
                'Myvarbinary3' => str_repeat('v', 100),
                'Myvarbinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM varbinary WHERE id = 1
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
