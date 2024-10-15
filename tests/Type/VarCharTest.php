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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/char-and-varchar-transact-sql?view=sql-server-ver16
 */
final class VarCharTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\VarCharProvider::columns
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

        $tableSchema = $db->getTableSchema('varchar_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('varchar_default')->execute();
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
        $command->insert('varchar_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[varchar_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('varchar_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\VarCharProvider::columns
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
        $this->setFixture('Type/varchar.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('varchar_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('varchar_default')->execute();
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
        $this->setFixture('Type/varchar.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('varchar_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[varchar_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('varchar_default')->execute();
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
        $this->setFixture('Type/varchar.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'varchar',
            [
                'Myvarchar1' => '0123456789',
                'Myvarchar2' => null,
                'Myvarchar3' => str_repeat('b', 100),
                'Myvarchar4' => null,
            ],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarchar1' => '0123456789',
                'Myvarchar2' => null,
                'Myvarchar3' => str_repeat('b', 100),
                'Myvarchar4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[varchar]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('varchar')->execute();
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
        $this->setFixture('Type/varchar.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]String or binary data would be truncated'
        );

        $command->insert('varchar', ['Myvarchar1' => '01234567891'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('varchar_default') !== null) {
            $command->dropTable('varchar_default')->execute();
        }

        $command->createTable(
            'varchar_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myvarchar1' => 'VARCHAR(10) DEFAULT \'varchar\'',
                'Myvarchar2' => 'VARCHAR(100) DEFAULT \'v\'',
                'Myvarchar3' => 'VARCHAR(20) DEFAULT TRY_CONVERT(varchar(20), year(getdate()))',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myvarchar1' => 'varchar',
            'Myvarchar2' => 'v',
            'Myvarchar3' => date('Y'),
        ];
    }
}
