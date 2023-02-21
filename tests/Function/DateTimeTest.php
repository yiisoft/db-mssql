<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

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
    public function testDefaultValue(): void
    {
        $this->setFixture('Function/datetime.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('datetime', [])->execute();
        $tableSchema = $db->getTableSchema('datetime');

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertInstanceOf(Expression::class, $tableSchema?->getColumn('Mydatetime1')->getDefaultValue());
        $this->assertSame(
            "CONVERT([varchar](10),datediff(day,'2005-12-31','2006-01-01'))+' days'",
            $tableSchema?->getColumn('Mydatetime1')->getDefaultValue()->__toString(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertInstanceOf(Expression::class, $tableSchema?->getColumn('Mydatetime2')->getDefaultValue());
        $this->assertSame(
            "datename(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime2')->getDefaultValue()->__toString(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datepart-transact-sql?view=sql-server-ver16 */
        $this->assertInstanceOf(Expression::class, $tableSchema?->getColumn('Mydatetime3')->getDefaultValue());
        $this->assertSame(
            "datepart(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime3')->getDefaultValue()->__toString(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/day-transact-sql?view=sql-server-ver16 */
        $this->assertInstanceOf(Expression::class, $tableSchema?->getColumn('Mydatetime4')->getDefaultValue());
        $this->assertSame(
            "datepart(day,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime4')->getDefaultValue()->__toString(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/month-transact-sql?view=sql-server-ver16 */
        $this->assertInstanceOf(Expression::class, $tableSchema?->getColumn('Mydatetime5')->getDefaultValue());
        $this->assertSame(
            "datepart(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime5')->getDefaultValue()->__toString(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/year-transact-sql?view=sql-server-ver16 */
        $this->assertInstanceOf(Expression::class, $tableSchema?->getColumn('Mydatetime6')->getDefaultValue());
        $this->assertSame(
            "datepart(year,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime6')->getDefaultValue()->__toString(),
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
            )->queryOne()
        );
    }
}
