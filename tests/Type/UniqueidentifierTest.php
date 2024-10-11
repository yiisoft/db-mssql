<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

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
 *
* @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/uniqueidentifier-transact-sql?view=sql-server-ver16
 */
final class UniqueidentifierTest extends TestCase
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
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('uniqueidentifier_default');

        $this->assertSame('uniqueidentifier', $tableSchema?->getColumn('Myuniqueidentifier')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myuniqueidentifier')->getPhpType());
        $this->assertSame(
            '12345678-1234-1234-1234-123456789012',
            $tableSchema?->getColumn('Myuniqueidentifier')->getDefaultValue(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
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
        $command->insert('uniqueidentifier_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[uniqueidentifier_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
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
        $this->setFixture('Type/uniqueidentifier.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('uniqueidentifier_default');

        $this->assertSame('uniqueidentifier', $tableSchema?->getColumn('Myuniqueidentifier')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myuniqueidentifier')->getPhpType());
        $this->assertSame(
            '12345678-1234-1234-1234-123456789012',
            $tableSchema?->getColumn('Myuniqueidentifier')->getDefaultValue(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
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
        $this->setFixture('Type/uniqueidentifier.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('uniqueidentifier_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[uniqueidentifier_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
    }

    /**
     * Max value is 36 characters.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testValue(): void
    {
        $this->setFixture('Type/uniqueidentifier.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'uniqueidentifier',
            ['Myuniqueidentifier1' => '12345678-1234-1234-1234-123456789012', 'Myuniqueidentifier2' => null],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myuniqueidentifier1' => '12345678-1234-1234-1234-123456789012',
                'Myuniqueidentifier2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[uniqueidentifier]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('uniqueidentifier')->execute();
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
        $this->setFixture('Type/uniqueidentifier.sql');

        $db = $this->getConnection(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Conversion failed when converting from a character string to uniqueidentifier.'
        );

        $command = $db->createCommand();
        $command->insert('uniqueidentifier', ['Myuniqueidentifier1' => '1'])->execute();
    }

    /**
     * When you insert a value that is longer than 36 characters, the value is truncated to 36 characters.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testValueLength(): void
    {
        $this->setFixture('Type/uniqueidentifier.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'uniqueidentifier',
            ['Myuniqueidentifier1' => '12345678-1234-1234-1234-1234567890123', 'Myuniqueidentifier2' => null],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myuniqueidentifier1' => '12345678-1234-1234-1234-123456789012',
                'Myuniqueidentifier2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[uniqueidentifier]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('uniqueidentifier')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('uniqueidentifier_default') !== null) {
            $command->dropTable('uniqueidentifier_default')->execute();
        }

        $command->createTable(
            'uniqueidentifier_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myuniqueidentifier' => 'UNIQUEIDENTIFIER DEFAULT \'12345678-1234-1234-1234-123456789012\'',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myuniqueidentifier' => '12345678-1234-1234-1234-123456789012',
        ];
    }
}
