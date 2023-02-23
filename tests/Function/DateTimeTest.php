<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

use DateTime;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
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
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\DateTimeProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTable(
        string $column,
        string $dbType,
        string $phpType,
        string $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('datetime');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('datetime')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('datetime', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mydate1], [Mydate2], [Mydate3], [Mydatetime1], [Mydatetime2], [Mydatetime3], [Mydatetime4], [Mydatetime5], [Mydatetime6] FROM [datetime] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetime7] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.uv P',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetimeoffset1] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.uv P',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetimeoffset2] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'H:i:s.uv',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mytime] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $db->createCommand()->dropTable('datetime')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\DateTimeProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        string $defaultValue
    ): void {
        $this->setFixture('Function/datetime.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('datetime');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('datetime')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValueWithInsert(): void
    {
        $this->setFixture('Function/datetime.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $command->insert('datetime', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mydate1], [Mydate2], [Mydate3], [Mydatetime1], [Mydatetime2], [Mydatetime3], [Mydatetime4], [Mydatetime5], [Mydatetime6] FROM [datetime] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetime7] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.uv P',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetimeoffset1] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.uv P',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetimeoffset2] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'H:i:s.uv',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mytime] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $db->createCommand()->dropTable('datetime')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('datetime') !== null) {
            $command->dropTable('datetime')->execute();
        }

        $command->createTable(
            'datetime',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mydate1' => 'DATE NOT NULL DEFAULT GETUTCDATE()',
                'Mydate2' => 'DATE NOT NULL DEFAULT GETDATE()',
                'Mydate3' => 'DATE NOT NULL DEFAULT DATEADD(month, 1, \'2006-08-31\')',
                'Mydatetime1' => 'VARCHAR(10) NOT NULL DEFAULT CAST(datediff(day, \'2005-12-31\', \'2006-01-01\') AS varchar(10)) + \' days\'',
                'Mydatetime2' => 'VARCHAR(10) NOT NULL DEFAULT DATENAME(month,\'2023-02-21\')',
                'Mydatetime3' => 'INT NOT NULL DEFAULT DATEPART(month,\'2023-02-21\')',
                'Mydatetime4' => 'INT NOT NULL DEFAULT DAY(\'2023-02-21\')',
                'Mydatetime5' => 'INT NOT NULL DEFAULT MONTH(\'2023-02-21\')',
                'Mydatetime6' => 'INT NOT NULL DEFAULT YEAR(\'2023-02-21\')',
                'Mydatetime7' => 'DATETIME NOT NULL DEFAULT SYSDATETIME()',
                'Mydatetimeoffset1' => 'DATETIMEOFFSET NOT NULL DEFAULT SYSDATETIMEOFFSET()',
                'Mydatetimeoffset2' => 'DATETIMEOFFSET NOT NULL DEFAULT SYSUTCDATETIME()',
                'Mytime' => 'TIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mydate1' => date('Y-m-d'),
            'Mydate2' => date('Y-m-d'),
            'Mydate3' => '2006-09-30',
            'Mydatetime1' => '1 days',
            'Mydatetime2' => 'February',
            'Mydatetime3' => '2',
            'Mydatetime4' => '21',
            'Mydatetime5' => '2',
            'Mydatetime6' => '2023',
        ];
    }
}
