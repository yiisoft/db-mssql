<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/char-and-varchar-transact-sql?view=sql-server-ver16
 */
final class CharTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\CharProvider::columns
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        string $defaultValue,
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('char_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('char_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('char_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[char_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('char_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\CharProvider::columns
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        string $defaultValue,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_CHAR);

        $tableSchema = $db->getTableSchema('char_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('char_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_CHAR);

        $command = $db->createCommand();
        $command->insert('char_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[char_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('char_default')->execute();
    }

    public function testValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_CHAR);

        $command = $db->createCommand();
        $command->insert(
            'char',
            [
                'Mychar1' => '0123456789',
                'Mychar2' => null,
                'Mychar3' => 'b',
                'Mychar4' => null,
            ],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mychar1' => '0123456789',
                'Mychar2' => null,
                'Mychar3' => 'b',
                'Mychar4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM char WHERE id = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('char')->execute();
    }

    public function testValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_CHAR);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]String or binary data would be truncated',
        );

        $command->insert('char', ['Mychar1' => '01234567891'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('char_default') !== null) {
            $command->dropTable('char_default')->execute();
        }

        $command->createTable(
            'char_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mychar1' => 'CHAR(10) DEFAULT \'char\'', // Max value
                'Mychar2' => 'CHAR(1) DEFAULT \'c\'', // Max value
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mychar1' => 'char      ',
            'Mychar2' => 'c',
        ];
    }
}
