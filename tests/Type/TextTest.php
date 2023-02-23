<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/ntext-text-and-image-transact-sql?view=sql-server-ver16
 */
final class TextTest extends TestCase
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
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('text_default') !== null) {
            $command->dropTable('text_default')->execute();
        }

        $command->createTable(
            'text_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mytext' => 'TEXT DEFAULT \'text\'',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('text_default');

        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytext')->getPhpType());
        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDefaultValue());
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
        $this->setFixture('Type/text.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('text_default');

        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytext')->getPhpType());
        $this->assertSame('text', $tableSchema?->getColumn('Mytext')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('text_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytext' => 'text',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM text_default WHERE id = 1
                SQL
            )->queryOne()
        );
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
        $this->setFixture('Type/text.sql');

        $db = $this->getConnection(true);
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
                SELECT * FROM text WHERE id = 1
                SQL
            )->queryOne()
        );
    }
}
