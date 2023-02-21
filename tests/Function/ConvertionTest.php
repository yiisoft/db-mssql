<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
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
    public function testCastDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('cast', [])->execute();
        $tableSchema = $db->getTableSchema('cast');

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mycast1')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([int],'1')",
            $tableSchema?->getColumn('Mycast1')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mycast2')->getDefaultValue(),
        );
        $this->assertSame(
            'CONVERT([int],(14.85))',
            $tableSchema?->getColumn('Mycast2')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mycast3')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([float],'14.85')",
            $tableSchema?->getColumn('Mycast3')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mycast4')->getDefaultValue(),
        );
        $this->assertSame(
            'CONVERT([varchar](4),(15.6))',
            $tableSchema?->getColumn('Mycast4')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mycast5')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([datetime],'2023-02-21')",
            $tableSchema?->getColumn('Mycast5')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mycast6')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([binary],'testme')",
            $tableSchema?->getColumn('Mycast6')->getDefaultValue()->__toString(),
        );

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
    public function testConvertDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('convert', [])->execute();
        $tableSchema = $db->getTableSchema('convert');

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Myconvert1')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([int],'1')",
            $tableSchema?->getColumn('Myconvert1')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Myconvert2')->getDefaultValue(),
        );
        $this->assertSame(
            'CONVERT([int],(14.85))',
            $tableSchema?->getColumn('Myconvert2')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Myconvert3')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([float],'14.85')",
            $tableSchema?->getColumn('Myconvert3')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Myconvert4')->getDefaultValue(),
        );
        $this->assertSame(
            'CONVERT([varchar](4),(15.6))',
            $tableSchema?->getColumn('Myconvert4')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Myconvert5')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([datetime],'2023-02-21')",
            $tableSchema?->getColumn('Myconvert5')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Myconvert6')->getDefaultValue(),
        );
        $this->assertSame(
            "CONVERT([binary],'testme')",
            $tableSchema?->getColumn('Myconvert6')->getDefaultValue()->__toString(),
        );

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
    public function testTryCasttDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('trycast', [])->execute();
        $tableSchema = $db->getTableSchema('trycast');

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytrycast1')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('1' AS [int])",
            $tableSchema?->getColumn('Mytrycast1')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytrycast2')->getDefaultValue(),
        );
        $this->assertSame(
            'TRY_CAST((14.85) AS [int])',
            $tableSchema?->getColumn('Mytrycast2')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytrycast3')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('14.85' AS [float])",
            $tableSchema?->getColumn('Mytrycast3')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytrycast4')->getDefaultValue(),
        );
        $this->assertSame(
            'TRY_CAST((15.6) AS [varchar](4))',
            $tableSchema?->getColumn('Mytrycast4')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytrycast5')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('2023-02-21' AS [datetime])",
            $tableSchema?->getColumn('Mytrycast5')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytrycast6')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('testme' AS [binary])",
            $tableSchema?->getColumn('Mytrycast6')->getDefaultValue()->__toString(),
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
    public function testTryConverDefaultValue(): void
    {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('tryconvert', [])->execute();
        $tableSchema = $db->getTableSchema('tryconvert');

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytryconvert1')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('1' AS [int])",
            $tableSchema?->getColumn('Mytryconvert1')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytryconvert2')->getDefaultValue(),
        );
        $this->assertSame(
            'TRY_CAST((14.85) AS [int])',
            $tableSchema?->getColumn('Mytryconvert2')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytryconvert3')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('14.85' AS [float])",
            $tableSchema?->getColumn('Mytryconvert3')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytryconvert4')->getDefaultValue(),
        );
        $this->assertSame(
            'TRY_CAST((15.6) AS [varchar](4))',
            $tableSchema?->getColumn('Mytryconvert4')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytryconvert5')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('2023-02-21' AS [datetime])",
            $tableSchema?->getColumn('Mytryconvert5')->getDefaultValue()->__toString(),
        );

        $this->assertInstanceOf(
            ExpressionInterface::class,
            $tableSchema?->getColumn('Mytryconvert6')->getDefaultValue(),
        );
        $this->assertSame(
            "TRY_CAST('testme' AS [binary])",
            $tableSchema?->getColumn('Mytryconvert6')->getDefaultValue()->__toString(),
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
