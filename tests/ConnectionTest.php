<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractConnectionTest;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group mssql
 */
final class ConnectionTest extends AbstractConnectionTest
{
    use TestTrait;

    public function testGetDriverName(): void
    {
        $db = $this->getConnection();

        $this->assertSame('sqlsrv', $db->getDriver()->getDriverName());
    }

    public function testGetName(): void
    {
        $db = $this->getConnection();

        $this->assertSame('sqlsrv', $db->getName());
    }

    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testTransactionIsolation(): void
    {
        $db = $this->getConnection();

        $transaction = $db->beginTransaction(TransactionInterface::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::READ_COMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::SERIALIZABLE);
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);
    }

    public function testTransactionShortcutCustom(): void
    {
        $db = $this->getConnection();

        $result = $db->transaction(
            static function (ConnectionInterface $db): bool {
                $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
                return true;
            },
            TransactionInterface::READ_UNCOMMITTED,
        );
        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';"
        )->queryScalar();

        $this->assertSame('1', $profilesCount, 'profile should be inserted in transaction shortcut');
    }
}
