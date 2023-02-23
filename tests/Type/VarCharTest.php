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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/char-and-varchar-transact-sql?view=sql-server-ver16
 */
final class VarCharTest extends TestCase
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
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('varchar_default') !== null) {
            $command->dropTable('varchar_default')->execute();
        }

        $command->createTable(
            'varchar_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myvarchar1' => 'VARCHAR(10) DEFAULT \'varchar\'',
                'Myvarchar2' => 'VARCHAR(100) DEFAULT \'v\'',
                'Myvarchar3' => 'VARCHAR(20) DEFAULT TRY_CONVERT(varchar(20), getdate())',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('varchar_default');

        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myvarchar1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myvarchar1')->getPhpType());
        $this->assertSame('varchar', $tableSchema?->getColumn('Myvarchar1')->getDefaultValue());

        $this->assertSame('varchar(100)', $tableSchema?->getColumn('Myvarchar2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myvarchar2')->getPhpType());
        $this->assertSame('v', $tableSchema?->getColumn('Myvarchar2')->getDefaultValue());

        $this->assertSame('varchar(20)', $tableSchema?->getColumn('Myvarchar3')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myvarchar3')->getPhpType());
        $this->assertSame(
            'TRY_CAST(getdate() AS [varchar](20))',
            $tableSchema?->getColumn('Myvarchar3')->getDefaultValue(),
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
        $this->setFixture('Type/varchar.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('varchar_default');

        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myvarchar1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myvarchar1')->getPhpType());
        $this->assertSame('varchar', $tableSchema?->getColumn('Myvarchar1')->getDefaultValue());

        $this->assertSame('varchar(100)', $tableSchema?->getColumn('Myvarchar2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myvarchar2')->getPhpType());
        $this->assertSame('v', $tableSchema?->getColumn('Myvarchar2')->getDefaultValue());

        $this->assertSame('varchar(20)', $tableSchema?->getColumn('Myvarchar3')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myvarchar3')->getPhpType());
        $this->assertSame(
            'TRY_CAST(getdate() AS [varchar](20))',
            $tableSchema?->getColumn('Myvarchar3')->getDefaultValue(),
        );

        $command = $db->createCommand();
        $command->insert('varchar_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarchar1' => 'varchar',
                'Myvarchar2' => 'v',
            ],
            $command->setSql(
                <<<SQL
                SELECT id, Myvarchar1, Myvarchar2 FROM varchar_default WHERE id = 1
                SQL
            )->queryOne()
        );

        $this->assertStringContainsString(
            date('M j Y'),
            $command->setSql(
                <<<SQL
                SELECT Myvarchar3 FROM varchar_default WHERE id = 1
                SQL
            )->queryScalar(),
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
        $this->setFixture('Type/varchar.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'varchar',
            [
                'Myvarchar1' => '0123456789',
                'Myvarchar2' => null,
                'Myvarchar3' => str_repeat('b', 100),
                'Myvarchar4' => null,
            ],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarchar1' => '0123456789',
                'Myvarchar2' => null,
                'Myvarchar3' => str_repeat('b', 100),
                'Myvarchar4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM varchar WHERE id = 1
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
    public function testValueException(): void
    {
        $this->setFixture('Type/varchar.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[Microsoft][ODBC Driver 17 for SQL Server][SQL Server]String or binary data would be truncated'
        );

        $command->insert('varchar', ['Myvarchar1' => '01234567891'])->execute();
    }
}
