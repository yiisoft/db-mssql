<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/rowversion-transact-sql?view=sql-server-ver16
 */
final class RowversionTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/rowversion.sql');

        $tableSchema = $db->getTableSchema('rowversion');

        $this->assertSame('timestamp', $tableSchema?->getColumn('Myrowversion')->getDbType());
        $this->assertNull($tableSchema?->getColumn('Myrowversion')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('rowversion', [])->execute();

        $this->assertIsNumeric(
            $command->setSql(
                <<<SQL
                SELECT CONVERT(BIGINT, [[Myrowversion]], 1) as [[Myrowversion]] FROM [[rowversion]] WHERE [[id]] = 1
                SQL,
            )->queryScalar(),
        );
    }
}
