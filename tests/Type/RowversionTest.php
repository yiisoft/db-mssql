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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/rowversion-transact-sql?view=sql-server-ver16
 */
final class RowversionTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testValue(): void
    {
        $this->setFixture('Type/rowversion.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('rowversion');

        $this->assertSame('timestamp', $tableSchema?->getColumn('Myrowversion')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myrowversion')->getPhpType());
        $this->assertNull($tableSchema?->getColumn('Myrowversion')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('rowversion', [])->execute();

        $this->assertIsNumeric(
            $command->setSql(
                <<<SQL
                SELECT CONVERT(BIGINT, [[Myrowversion]], 1) as [[Myrowversion]] FROM [[rowversion]] WHERE [[id]] = 1
                SQL
            )->queryScalar()
        );
    }
}
