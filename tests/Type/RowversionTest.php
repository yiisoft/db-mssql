<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/rowversion-transact-sql?view=sql-server-ver16
 */
final class RowversionTest extends TestCase
{
    use TestTrait;

    public function testValue(): void
    {
        $this->setFixture('rowversion.sql');

        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema('rowversion');

        $this->assertSame('timestamp', $tableSchema->getColumn('Myrowversion')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Myrowversion')->getPhpType());

        $command = $db->createCommand();
        $command->insert('rowversion', [])->execute();

        $this->assertIsNumeric(
            $command->setSql(
                <<<SQL
                SELECT CONVERT(BIGINT, Myrowversion, 1) as Myrowversion FROM rowversion WHERE id = 1
                SQL
            )->queryScalar()
        );
    }
}
