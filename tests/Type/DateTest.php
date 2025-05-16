<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
* @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/date-transact-sql?view=sql-server-ver16
 */
final class DateTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\DateProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        DateTimeImmutable $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('date_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->insert('date_default', [])->execute();
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
        $command->insert('date_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[date_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('date_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\DateProvider::columns
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
        DateTimeImmutable $defaultValue
    ): void {
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('date_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('date_default')->execute();
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
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('date_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[date_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('date_default')->execute();
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
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand()->withDbTypecasting(false);
        $command->insert('date', [
            'Mydate1' => '2007-05-08',
            'Mydate2' => null,
            'Mydatetime1' => '2007-05-08 12:35:29.123',
            'Mydatetime2' => null,
            'Mydatetimeoffset1' => '2007-05-08 12:35:29.1234567 +12:15',
            'Mydatetimeoffset2' => null,
            'Mytime1' => '12:35:29.1234567',
            'Mytime2' => null,
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate1' => '2007-05-08',
                'Mydate2' => null,
                'Mydatetime1' => '2007-05-08 12:35:29.123',
                'Mydatetime2' => null,
                'Mydatetimeoffset1' => '2007-05-08 12:35:29.1234567 +12:15',
                'Mydatetimeoffset2' => null,
                'Mytime1' => '12:35:29.1234567',
                'Mytime2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[date]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('date')->execute();
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
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Conversion failed when converting date and/or time from character string.'
        );

        $db->createCommand()->insert('date', ['Mydate1' => '0000-00-00'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('date_default') !== null) {
            $command->dropTable('date_default')->execute();
        }

        $command->createTable(
            'date_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mydate' => 'DATE DEFAULT \'2007-05-08\'',
                'Mydatetime' => 'DATETIME DEFAULT \'2007-05-08 12:35:29.123\'',
                'Mydatetime2' => ColumnBuilder::datetime(7)->defaultValue(new Expression("'2007-05-08 12:35:29.1234567'")),
                'Mydatetimeoffset' => ColumnBuilder::datetimeWithTimezone(7)->defaultValue(new Expression("'2007-05-08 12:35:29.1234567 +12:15'")),
                'Mytime' => ColumnBuilder::time(7)->defaultValue(new Expression("'12:35:29.1234567'")),
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mydate' => '2007-05-08',
            'Mydatetime' => '2007-05-08 12:35:29.123',
            'Mydatetime2' => '2007-05-08 12:35:29.1234567',
            'Mydatetimeoffset' => '2007-05-08 12:35:29.1234567 +12:15',
            'Mytime' => '12:35:29.1234567',
        ];
    }
}
