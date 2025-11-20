<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
* @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/date-transact-sql?view=sql-server-ver16
 */
final class DateTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\DateProvider::columns
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        DateTimeImmutable $defaultValue,
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('date_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->insert('date_default', [])->execute();
    }

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
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('date_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\DateProvider::columns
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        DateTimeImmutable $defaultValue,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_DATE);

        $tableSchema = $db->getTableSchema('date_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('date_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_DATE);

        $command = $db->createCommand();
        $command->insert('date_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[date_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('date_default')->execute();
    }

    public function testValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_DATE);

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
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('date')->execute();
    }

    public function testValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_DATE);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Conversion failed when converting date and/or time from character string.',
        );

        $db->createCommand()->insert('date', ['Mydate1' => '0000-00-00'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

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
