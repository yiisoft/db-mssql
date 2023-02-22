<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

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
 */
final class ConvertionTest extends TestCase
{
    use TestTrait;

    /**
     * @link https://learn.microsoft.com/es-es/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCastCreateTableDefaultValue(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('cast') !== null) {
            $command->dropTable('cast')->execute();
        }

        $command->createTable(
            'cast',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mycast1' => 'INT NOT NULL DEFAULT CAST(\'1\' AS INT)',
                'Mycast2' => 'INT NOT NULL DEFAULT CAST(14.85 AS INT)',
                'Mycast3' => 'FLOAT NOT NULL DEFAULT CAST(\'14.85\' AS FLOAT)',
                'Mycast4' => 'VARCHAR(4) NOT NULL DEFAULT CAST(15.6 AS VARCHAR(4))',
                'Mycast5' => 'DATETIME NOT NULL DEFAULT CAST(\'2023-02-21\' AS DATETIME)',
                'Mycast6' => 'BINARY(10) NOT NULL DEFAULT CAST(\'testme\' AS BINARY(10))',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('cast');

        $this->assertSame('int', $tableSchema?->getColumn('Mycast1')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mycast1')->getPhpType());
        $this->assertSame('CONVERT([int],\'1\')', $tableSchema?->getColumn('Mycast1')->getDefaultValue());

        $this->assertSame('int', $tableSchema?->getColumn('Mycast2')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mycast2')->getPhpType());
        $this->assertSame('CONVERT([int],(14.85))', $tableSchema?->getColumn('Mycast2')->getDefaultValue());

        $this->assertSame('float', $tableSchema?->getColumn('Mycast3')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Mycast3')->getPhpType());
        $this->assertSame('CONVERT([float],\'14.85\')', $tableSchema?->getColumn('Mycast3')->getDefaultValue());

        $this->assertSame('varchar(4)', $tableSchema?->getColumn('Mycast4')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mycast4')->getPhpType());
        $this->assertSame('CONVERT([varchar](4),(15.6))', $tableSchema?->getColumn('Mycast4')->getDefaultValue());

        $this->assertSame('datetime', $tableSchema?->getColumn('Mycast5')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mycast5')->getPhpType());
        $this->assertSame('CONVERT([datetime],\'2023-02-21\')', $tableSchema?->getColumn('Mycast5')->getDefaultValue());

        $this->assertSame('binary(10)', $tableSchema?->getColumn('Mycast6')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mycast6')->getPhpType());
        $this->assertSame('CONVERT([binary](10),\'testme\')', $tableSchema?->getColumn('Mycast6')->getDefaultValue());
    }

    /**
     * @link https://learn.microsoft.com/es-es/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCastDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('cast');

        $this->assertSame('int', $tableSchema?->getColumn('Mycast1')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mycast1')->getPhpType());
        $this->assertSame('CONVERT([int],\'1\')', $tableSchema?->getColumn('Mycast1')->getDefaultValue());

        $this->assertSame('int', $tableSchema?->getColumn('Mycast2')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mycast2')->getPhpType());
        $this->assertSame('CONVERT([int],(14.85))', $tableSchema?->getColumn('Mycast2')->getDefaultValue());

        $this->assertSame('float', $tableSchema?->getColumn('Mycast3')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Mycast3')->getPhpType());
        $this->assertSame('CONVERT([float],\'14.85\')', $tableSchema?->getColumn('Mycast3')->getDefaultValue());

        $this->assertSame('varchar(4)', $tableSchema?->getColumn('Mycast4')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mycast4')->getPhpType());
        $this->assertSame('CONVERT([varchar](4),(15.6))', $tableSchema?->getColumn('Mycast4')->getDefaultValue());

        $this->assertSame('datetime', $tableSchema?->getColumn('Mycast5')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mycast5')->getPhpType());
        $this->assertSame('CONVERT([datetime],\'2023-02-21\')', $tableSchema?->getColumn('Mycast5')->getDefaultValue());

        $this->assertSame('binary(10)', $tableSchema?->getColumn('Mycast6')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mycast6')->getPhpType());
        $this->assertSame('CONVERT([binary],\'testme\')', $tableSchema?->getColumn('Mycast6')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('cast', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mycast1' => '1',
                'Mycast2' => '14',
                'Mycast3' => '14.85',
                'Mycast4' => '15.6',
                'Mycast5' => '2023-02-21 00:00:00.000',
                'Mycast6' => '0x74657374',
            ],
            $command->setSql(
                <<<SQL
                SELECT [id], [Mycast1], [Mycast2], [Mycast3], [Mycast4], [Mycast5], CONVERT(VARCHAR(10), [Mycast6], 1) [Mycast6] FROM [cast] WHERE [id] = 1
                SQL
            )->queryOne(),
        );
    }

    /**
     * @link https://learn.microsoft.com/es-es/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testConvertCreateTableDefaultValue(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('convert') !== null) {
            $command->dropTable('convert')->execute();
        }

        $command->createTable(
            'convert',
            [
                'id' => 'int IDENTITY(1,1) PRIMARY KEY',
                'Myconvert1' => 'int NOT NULL DEFAULT CONVERT([int],\'1\')',
                'Myconvert2' => 'int NOT NULL DEFAULT CONVERT([int],(14.85))',
                'Myconvert3' => 'float NOT NULL DEFAULT CONVERT([float],\'14.85\')',
                'Myconvert4' => 'varchar(4) NOT NULL DEFAULT CONVERT([varchar](4),(15.6))',
                'Myconvert5' => 'datetime NOT NULL DEFAULT CONVERT([datetime],\'2023-02-21\')',
                'Myconvert6' => 'varbinary(10) NOT NULL DEFAULT CONVERT([varbinary](10),\'testme\')',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('convert');

        $this->assertSame('int', $tableSchema?->getColumn('Myconvert1')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Myconvert1')->getPhpType());
        $this->assertSame('CONVERT([int],\'1\')', $tableSchema?->getColumn('Myconvert1')->getDefaultValue());

        $this->assertSame('int', $tableSchema?->getColumn('Myconvert2')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Myconvert2')->getPhpType());
        $this->assertSame('CONVERT([int],(14.85))', $tableSchema?->getColumn('Myconvert2')->getDefaultValue());

        $this->assertSame('float', $tableSchema?->getColumn('Myconvert3')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Myconvert3')->getPhpType());
        $this->assertSame('CONVERT([float],\'14.85\')', $tableSchema?->getColumn('Myconvert3')->getDefaultValue());

        $this->assertSame('varchar(4)', $tableSchema?->getColumn('Myconvert4')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconvert4')->getPhpType());
        $this->assertSame('CONVERT([varchar](4),(15.6))', $tableSchema?->getColumn('Myconvert4')->getDefaultValue());

        $this->assertSame('datetime', $tableSchema?->getColumn('Myconvert5')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconvert5')->getPhpType());
        $this->assertSame(
            'CONVERT([datetime],\'2023-02-21\')',
            $tableSchema?->getColumn('Myconvert5')->getDefaultValue(),
        );

        $this->assertSame('varbinary(10)', $tableSchema?->getColumn('Myconvert6')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myconvert6')->getPhpType());
        $this->assertSame(
            'CONVERT([varbinary](10),\'testme\')',
            $tableSchema?->getColumn('Myconvert6')->getDefaultValue(),
        );
    }

    /**
     * @link https://learn.microsoft.com/es-es/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testConvertDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('convert', [])->execute();
        $tableSchema = $db->getTableSchema('convert');

        $this->assertSame('int', $tableSchema?->getColumn('Myconvert1')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Myconvert1')->getPhpType());
        $this->assertSame('CONVERT([int],\'1\')', $tableSchema?->getColumn('Myconvert1')->getDefaultValue());

        $this->assertSame('int', $tableSchema?->getColumn('Myconvert2')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Myconvert2')->getPhpType());
        $this->assertSame('CONVERT([int],(14.85))', $tableSchema?->getColumn('Myconvert2')->getDefaultValue());

        $this->assertSame('float', $tableSchema?->getColumn('Myconvert3')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Myconvert3')->getPhpType());
        $this->assertSame('CONVERT([float],\'14.85\')', $tableSchema?->getColumn('Myconvert3')->getDefaultValue());

        $this->assertSame('varchar(4)', $tableSchema?->getColumn('Myconvert4')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconvert4')->getPhpType());
        $this->assertSame('CONVERT([varchar](4),(15.6))', $tableSchema?->getColumn('Myconvert4')->getDefaultValue());

        $this->assertSame('datetime', $tableSchema?->getColumn('Myconvert5')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconvert5')->getPhpType());
        $this->assertSame(
            'CONVERT([datetime],\'2023-02-21\')',
            $tableSchema?->getColumn('Myconvert5')->getDefaultValue(),
        );

        $this->assertSame('binary(10)', $tableSchema?->getColumn('Myconvert6')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Myconvert6')->getPhpType());
        $this->assertSame('CONVERT([binary],\'testme\')', $tableSchema?->getColumn('Myconvert6')->getDefaultValue());

        $this->assertSame(
            [
                'id' => '1',
                'Myconvert1' => '1',
                'Myconvert2' => '14',
                'Myconvert3' => '14.85',
                'Myconvert4' => '15.6',
                'Myconvert5' => '2023-02-21 00:00:00.000',
                'Myconvert6' => '0x74657374',
            ],
            $command->setSql(
                <<<SQL
                SELECT [id], [Myconvert1], [Myconvert2], [Myconvert3], [Myconvert4], [Myconvert5], CONVERT(VARCHAR(10), [Myconvert6], 1) [Myconvert6] FROM [convert] WHERE [id] = 1
                SQL
            )->queryOne(),
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/functions/try-cast-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testTryCastCreateTableDefaultValue(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('trycast') !== null) {
            $command->dropTable('trycast')->execute();
        }

        $command->createTable(
            'trycast',
            [
                'id' => $schema->createColumnSchemaBuilder('int')->notNull(),
                'Mytrycast1' => 'INT NOT NULL DEFAULT TRY_CAST(\'1\' AS int)',
                'Mytrycast2' => 'INT NOT NULL DEFAULT TRY_CAST((14.85) AS int)',
                'Mytrycast3' => 'FLOAT NOT NULL DEFAULT TRY_CAST(\'14.85\' AS float)',
                'Mytrycast4' => 'VARCHAR(4) NOT NULL DEFAULT TRY_CAST((15.6) AS varchar(4))',
                'Mytrycast5' => 'DATETIME NOT NULL DEFAULT TRY_CAST(\'2023-02-21\' AS datetime)',
                'Mytrycast6' => 'BINARY(10) NOT NULL DEFAULT TRY_CAST(\'testme\' AS binary(10))',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('trycast');

        $this->assertSame('int', $tableSchema?->getColumn('Mytrycast1')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mytrycast1')->getPhpType());
        $this->assertSame('TRY_CAST(\'1\' AS [int])', $tableSchema?->getColumn('Mytrycast1')->getDefaultValue());

        $this->assertSame('int', $tableSchema?->getColumn('Mytrycast2')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mytrycast2')->getPhpType());
        $this->assertSame('TRY_CAST((14.85) AS [int])', $tableSchema?->getColumn('Mytrycast2')->getDefaultValue());

        $this->assertSame('float', $tableSchema?->getColumn('Mytrycast3')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Mytrycast3')->getPhpType());
        $this->assertSame('TRY_CAST(\'14.85\' AS [float])', $tableSchema?->getColumn('Mytrycast3')->getDefaultValue());

        $this->assertSame('varchar(4)', $tableSchema?->getColumn('Mytrycast4')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytrycast4')->getPhpType());
        $this->assertSame(
            'TRY_CAST((15.6) AS [varchar](4))',
            $tableSchema?->getColumn('Mytrycast4')->getDefaultValue(),
        );

        $this->assertSame('datetime', $tableSchema?->getColumn('Mytrycast5')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytrycast5')->getPhpType());
        $this->assertSame(
            'TRY_CAST(\'2023-02-21\' AS [datetime])',
            $tableSchema?->getColumn('Mytrycast5')->getDefaultValue(),
        );

        $this->assertSame('binary(10)', $tableSchema?->getColumn('Mytrycast6')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mytrycast6')->getPhpType());
        $this->assertSame(
            'TRY_CAST(\'testme\' AS [binary](10))',
            $tableSchema?->getColumn('Mytrycast6')->getDefaultValue(),
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/functions/try-cast-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testTryCastDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('trycast', [])->execute();
        $tableSchema = $db->getTableSchema('trycast');

        $this->assertSame(
            "TRY_CAST('1' AS [int])",
            $tableSchema?->getColumn('Mytrycast1')->getDefaultValue(),
        );

        $this->assertSame(
            'TRY_CAST((14.85) AS [int])',
            $tableSchema?->getColumn('Mytrycast2')->getDefaultValue(),
        );

        $this->assertSame(
            "TRY_CAST('14.85' AS [float])",
            $tableSchema?->getColumn('Mytrycast3')->getDefaultValue(),
        );

        $this->assertSame(
            'TRY_CAST((15.6) AS [varchar](4))',
            $tableSchema?->getColumn('Mytrycast4')->getDefaultValue(),
        );

        $this->assertSame(
            "TRY_CAST('2023-02-21' AS [datetime])",
            $tableSchema?->getColumn('Mytrycast5')->getDefaultValue(),
        );

        $this->assertSame(
            "TRY_CAST('testme' AS [binary])",
            $tableSchema?->getColumn('Mytrycast6')->getDefaultValue(),
        );

        $this->assertSame(
            [
                'id' => '1',
                'Mytrycast1' => '1',
                'Mytrycast2' => '14',
                'Mytrycast3' => '14.85',
                'Mytrycast4' => '15.6',
                'Mytrycast5' => '2023-02-21 00:00:00.000',
                'Mytrycast' => '0x74657374',
            ],
            $command->setSql(
                <<<SQL
                SELECT [id], [Mytrycast1], [Mytrycast2], [Mytrycast3], [Mytrycast4], [Mytrycast5], CONVERT(VARCHAR(10), [Mytrycast6], 1) [Mytrycast] FROM [trycast] WHERE [id] = 1
                SQL
            )->queryOne(),
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/functions/try-convert-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testTryConverCreateTableDefaultValue(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('tryconvert') !== null) {
            $command->dropTable('tryconvert')->execute();
        }

        $command->createTable(
            'tryconvert',
            [
                'id' => $schema->createColumnSchemaBuilder('int')->notNull(),
                'Mytryconvert1' => 'INT NOT NULL DEFAULT TRY_CONVERT(int, \'1\')',
                'Mytryconvert2' => 'INT NOT NULL DEFAULT TRY_CONVERT(int, 14.85)',
                'Mytryconvert3' => 'FLOAT NOT NULL DEFAULT TRY_CONVERT(float, \'14.85\')',
                'Mytryconvert4' => 'VARCHAR(4) NOT NULL DEFAULT TRY_CONVERT(varchar(4), 15.6)',
                'Mytryconvert5' => 'DATETIME NOT NULL DEFAULT TRY_CONVERT(datetime, \'2023-02-21\')',
                'Mytryconvert6' => 'BINARY(10) NOT NULL DEFAULT TRY_CONVERT(binary(10), \'testme\')',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('tryconvert');

        $this->assertSame('int', $tableSchema?->getColumn('Mytryconvert1')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mytryconvert1')->getPhpType());
        $this->assertSame(
            "TRY_CAST('1' AS [int])",
            $tableSchema?->getColumn('Mytryconvert1')->getDefaultValue(),
        );

        $this->assertSame('int', $tableSchema?->getColumn('Mytryconvert2')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mytryconvert2')->getPhpType());
        $this->assertSame(
            'TRY_CAST((14.85) AS [int])',
            $tableSchema?->getColumn('Mytryconvert2')->getDefaultValue(),
        );

        $this->assertSame('float', $tableSchema?->getColumn('Mytryconvert3')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Mytryconvert3')->getPhpType());
        $this->assertSame(
            "TRY_CAST('14.85' AS [float])",
            $tableSchema?->getColumn('Mytryconvert3')->getDefaultValue(),
        );

        $this->assertSame('varchar(4)', $tableSchema?->getColumn('Mytryconvert4')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytryconvert4')->getPhpType());
        $this->assertSame(
            'TRY_CAST((15.6) AS [varchar](4))',
            $tableSchema?->getColumn('Mytryconvert4')->getDefaultValue(),
        );

        $this->assertSame('datetime', $tableSchema?->getColumn('Mytryconvert5')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytryconvert5')->getPhpType());
        $this->assertSame(
            "TRY_CAST('2023-02-21' AS [datetime])",
            $tableSchema?->getColumn('Mytryconvert5')->getDefaultValue(),
        );

        $this->assertSame('binary(10)', $tableSchema?->getColumn('Mytryconvert6')->getDbType());
        $this->assertSame('resource', $tableSchema?->getColumn('Mytryconvert6')->getPhpType());
        $this->assertSame(
            "TRY_CAST('testme' AS [binary](10))",
            $tableSchema?->getColumn('Mytryconvert6')->getDefaultValue(),
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/functions/try-convert-transact-sql?view=sql-server-ver16
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testTryConverDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('tryconvert', [])->execute();
        $tableSchema = $db->getTableSchema('tryconvert');

        $this->assertSame(
            "TRY_CAST('1' AS [int])",
            $tableSchema?->getColumn('Mytryconvert1')->getDefaultValue(),
        );

        $this->assertSame(
            'TRY_CAST((14.85) AS [int])',
            $tableSchema?->getColumn('Mytryconvert2')->getDefaultValue(),
        );

        $this->assertSame(
            "TRY_CAST('14.85' AS [float])",
            $tableSchema?->getColumn('Mytryconvert3')->getDefaultValue(),
        );

        $this->assertSame(
            'TRY_CAST((15.6) AS [varchar](4))',
            $tableSchema?->getColumn('Mytryconvert4')->getDefaultValue(),
        );

        $this->assertSame(
            "TRY_CAST('2023-02-21' AS [datetime])",
            $tableSchema?->getColumn('Mytryconvert5')->getDefaultValue(),
        );

        $this->assertSame(
            "TRY_CAST('testme' AS [binary])",
            $tableSchema?->getColumn('Mytryconvert6')->getDefaultValue(),
        );

        $this->assertSame(
            [
                'id' => '1',
                'Mytryconvert1' => '1',
                'Mytryconvert2' => '14',
                'Mytryconvert3' => '14.85',
                'Mytryconvert4' => '15.6',
                'Mytryconvert5' => '2023-02-21 00:00:00.000',
                'Mytryconvert6' => '0x74657374',
            ],
            $command->setSql(
                <<<SQL
                SELECT [id], [Mytryconvert1], [Mytryconvert2], [Mytryconvert3], [Mytryconvert4], [Mytryconvert5], CONVERT(VARCHAR(10), [Mytryconvert6], 1) [Mytryconvert6] FROM [tryconvert] WHERE [id] = 1
                SQL
            )->queryOne(),
        );
    }
}
