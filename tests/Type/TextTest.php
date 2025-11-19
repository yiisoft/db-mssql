<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function dirname;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/ntext-text-and-image-transact-sql?view=sql-server-ver16
 */
final class TextTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('text_default');

        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDbType());
        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDefaultValue());

        $db->createCommand()->dropTable('text_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('text_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[text_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('text_default')->execute();
    }

    public function testDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/text.sql');

        $tableSchema = $db->getTableSchema('text_default');

        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDbType());
        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDefaultValue());

        $db->createCommand()->dropTable('text_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/text.sql');

        $command = $db->createCommand();
        $command->insert('text_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[text_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('text_default')->execute();
    }

    public function testValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/text.sql');

        $command = $db->createCommand();
        $command->insert('text', ['Mytext1' => '0123456789', 'Mytext2' => null])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytext1' => '0123456789',
                'Mytext2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[text]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('text')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('text_default') !== null) {
            $command->dropTable('text_default')->execute();
        }

        $command->createTable(
            'text_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mytext' => 'TEXT DEFAULT \'text\'',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mytext' => 'text',
        ];
    }
}
