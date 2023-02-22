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
final class DateTimeTest extends TestCase
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

        if ($schema->getTableSchema('datetime') !== null) {
            $command->dropTable('datetime')->execute();
        }

        $command->createTable(
            'datetime',
            [
                'id' => $schema->createColumnSchemaBuilder('int')->notNull(),
                'Mydatetime1' => 'VARCHAR(10) NOT NULL DEFAULT CAST(datediff(day, \'2005-12-31\', \'2006-01-01\') AS varchar(10)) + \' days\'',
                'Mydatetime2' => 'VARCHAR(10) NOT NULL DEFAULT DATENAME(month,\'2023-02-21\')',
                'Mydatetime3' => 'INT NOT NULL DEFAULT DATEPART(month,\'2023-02-21\')',
                'Mydatetime4' => 'INT NOT NULL DEFAULT DAY(\'2023-02-21\')',
                'Mydatetime5' => 'INT NOT NULL DEFAULT MONTH(\'2023-02-21\')',
                'Mydatetime6' => 'INT NOT NULL DEFAULT YEAR(\'2023-02-21\')',
            ]
        )->execute();

        $tableSchema = $db->getTableSchema('datetime');

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            'CONVERT([varchar](10),datediff(day,\'2005-12-31\',\'2006-01-01\'))+\' days\'',
            $tableSchema?->getColumn('Mydatetime1')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datename-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            'datename(month,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime2')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datepart-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime3')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime3')->getPhpType());
        $this->assertSame(
            'datepart(month,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime3')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/day-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime4')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime4')->getPhpType());
        $this->assertSame(
            'datepart(day,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime4')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/month-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime5')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime5')->getPhpType());
        $this->assertSame(
            'datepart(month,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime5')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/year-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime6')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime6')->getPhpType());
        $this->assertSame(
            'datepart(year,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime6')->getDefaultValue(),
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
        $this->setFixture('Function/datetime.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('datetime', [])->execute();
        $tableSchema = $db->getTableSchema('datetime');

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            "CONVERT([varchar](10),datediff(day,'2005-12-31','2006-01-01'))+' days'",
            $tableSchema?->getColumn('Mydatetime1')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            "datename(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime2')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datepart-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime3')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime3')->getPhpType());
        $this->assertSame(
            "datepart(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime3')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/day-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime4')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime4')->getPhpType());
        $this->assertSame(
            "datepart(day,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime4')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/month-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime5')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime5')->getPhpType());
        $this->assertSame(
            "datepart(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime5')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/year-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime6')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime6')->getPhpType());
        $this->assertSame(
            "datepart(year,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime6')->getDefaultValue(),
        );

        $this->assertSame(
            [
                'id' => '1',
                'Mydatetime1' => '1 days',
                'Mydatetime2' => 'February',
                'Mydatetime3' => '2',
                'Mydatetime4' => '21',
                'Mydatetime5' => '2',
                'Mydatetime6' => '2023',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [datetime] WHERE [id] = 1
                SQL
            )->queryOne(),
        );
    }
}
