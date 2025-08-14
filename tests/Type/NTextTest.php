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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/ntext-text-and-image-transact-sql?view=sql-server-ver16
 */
final class NTextTest extends TestCase
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

        $tableSchema = $db->getTableSchema('ntext_default');

        $this->assertSame('ntext', $tableSchema?->getColumn('Myntext')->getDbType());
        $this->assertSame('ntext', $tableSchema?->getColumn('Myntext')->getDefaultValue());

        $db->createCommand()->dropTable('ntext_default')->execute();
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
        $command->insert('ntext_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[ntext_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('ntext_default')->execute();
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
        $this->setFixture('Type/ntext.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('ntext_default');

        $this->assertSame('ntext', $tableSchema?->getColumn('Myntext')->getDbType());
        $this->assertSame('ntext', $tableSchema?->getColumn('Myntext')->getDefaultValue());

        $db->createCommand()->dropTable('ntext_default')->execute();
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
        $this->setFixture('Type/ntext.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('ntext_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[ntext_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('ntext_default')->execute();
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
        $this->setFixture('Type/ntext.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('ntext', ['Myntext1' => '0123456789', 'Myntext2' => null])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myntext1' => '0123456789',
                'Myntext2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[ntext]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('ntext')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('ntext_default') !== null) {
            $command->dropTable('ntext_default')->execute();
        }

        $command->createTable(
            'ntext_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myntext' => 'NTEXT DEFAULT \'ntext\'',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myntext' => 'ntext',
        ];
    }
}
