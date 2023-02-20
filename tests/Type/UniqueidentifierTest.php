<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
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

    public function testDefaultValue(): void
    {
        $this->setFixture('uniqueidentifier.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('uniqueidentifier_default');

        $this->assertSame('uniqueidentifier', $tableSchema->getColumn('Myuniqueidentifier')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Myuniqueidentifier')->getPhpType());

        $command = $db->createCommand();
        $command->insert('uniqueidentifier_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myuniqueidentifier' => '12345678-1234-1234-1234-123456789012',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM uniqueidentifier_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is 36 characters.
     */
    public function testValue(): void
    {
        $this->setFixture('uniqueidentifier.sql');

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
                SELECT * FROM uniqueidentifier WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testValueException(): void
    {
        $this->setFixture('uniqueidentifier.sql');

        $db = $this->getConnection(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[42000]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Conversion failed when converting from a character string to uniqueidentifier.'
        );

        $command = $db->createCommand();
        $command->insert('uniqueidentifier', ['Myuniqueidentifier1' => '1'])->execute();
    }

    /**
     * When you insert a value that is longer than 36 characters, the value is truncated to 36 characters.
     */
    public function testValueLength(): void
    {
        $this->setFixture('uniqueidentifier.sql');

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
                SELECT * FROM uniqueidentifier WHERE id = 1
                SQL
            )->queryOne()
        );
    }
}
