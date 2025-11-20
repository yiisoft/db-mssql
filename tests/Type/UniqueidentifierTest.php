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
* @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/uniqueidentifier-transact-sql?view=sql-server-ver16
 */
final class UniqueidentifierTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('uniqueidentifier_default');

        $this->assertSame('uniqueidentifier', $tableSchema?->getColumn('Myuniqueidentifier')->getDbType());
        $this->assertSame(
            '12345678-1234-1234-1234-123456789012',
            $tableSchema?->getColumn('Myuniqueidentifier')->getDefaultValue(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
    }

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
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
    }

    public function testDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_UNIQUEIDENTIFIER);

        $tableSchema = $db->getTableSchema('uniqueidentifier_default');

        $this->assertSame('uniqueidentifier', $tableSchema?->getColumn('Myuniqueidentifier')->getDbType());
        $this->assertSame(
            '12345678-1234-1234-1234-123456789012',
            $tableSchema?->getColumn('Myuniqueidentifier')->getDefaultValue(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_UNIQUEIDENTIFIER);

        $command = $db->createCommand();
        $command->insert('uniqueidentifier_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[uniqueidentifier_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('uniqueidentifier_default')->execute();
    }

    /**
     * Max value is 36 characters.
     */
    public function testValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_UNIQUEIDENTIFIER);

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
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('uniqueidentifier')->execute();
    }

    public function testValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_UNIQUEIDENTIFIER);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Conversion failed when converting from a character string to uniqueidentifier.',
        );

        $command = $db->createCommand();
        $command->insert('uniqueidentifier', ['Myuniqueidentifier1' => '1'])->execute();
    }

    /**
     * When you insert a value that is longer than 36 characters, the value is truncated to 36 characters.
     */
    public function testValueLength(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_UNIQUEIDENTIFIER);

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
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('uniqueidentifier')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

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
