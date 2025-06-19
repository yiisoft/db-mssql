<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
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
final class CharTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\CharProvider::columns
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
        int $size,
        string $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('char_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('char_default')->execute();
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
        $command->insert('char_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[char_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('char_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\CharProvider::columns
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
        int $size,
        string $defaultValue
    ): void {
        $this->setFixture('Type/char.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('char_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('char_default')->execute();
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
        $this->setFixture('Type/char.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('char_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[char_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('char_default')->execute();
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
        $this->setFixture('Type/char.sql');

        $db = $this->getConnection(true);
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
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('char')->execute();
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
        $this->setFixture('Type/char.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]String or binary data would be truncated'
        );

        $command->insert('char', ['Mychar1' => '01234567891'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

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
