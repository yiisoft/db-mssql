<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/bit-transact-sql?view=sql-server-ver16
 */
final class BitTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\BitProvider::columns
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        bool $defaultValue,
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('bit_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('bit_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('bit_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[bit_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bit_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\BitProvider::columns
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        bool $defaultValue,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIT);

        $tableSchema = $db->getTableSchema('bit_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('bit_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIT);

        $command = $db->createCommand();
        $command->insert('bit_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[bit_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bit_default')->execute();
    }

    public function testBoolean(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIT);

        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => true, 'Mybit2' => false, 'Mybit3' => true])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '1',
                'Mybit2' => '0',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bit')->execute();
    }

    /**
     * Max value is `1`.
     */
    public function testMaxValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIT);

        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => 1, 'Mybit2' => 1, 'Mybit3' => 1])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '1',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('bit', ['Mybit1' => 0.5, 'Mybit2' => 3, 'Mybit3' => 4])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybit1' => '1',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bit')->execute();
    }

    /**
     * Min value is `0`.
     *
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/bit-transact-sql?view=sql-server-ver16#remarks
     */
    public function testMinValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIT);

        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => 0, 'Mybit2' => 0, 'Mybit3' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '0',
                'Mybit2' => '0',
                'Mybit3' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('bit', ['Mybit1' => null, 'Mybit2' => 0.8, 'Mybit3' => -3])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybit1' => null,
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bit')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('bit_default') !== null) {
            $command->dropTable('bit_default')->execute();
        }

        $command->createTable(
            'bit_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mybit1' => 'BIT DEFAULT 0', // Min value
                'Mybit2' => 'BIT DEFAULT 1', // Max value
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mybit1' => '0',
            'Mybit2' => '1',
        ];
    }
}
