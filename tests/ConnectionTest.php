<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Throwable;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionTest;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group mssql
 */
final class ConnectionTest extends CommonConnectionTest
{
    use TestTrait;

    /**
     * @throws Exception
     */
    public function testGetDriverName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('sqlsrv', $db->getDriver()->getDriverName());
    }

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('sqlsrv', $db->getName());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testTransactionIsolation(): void
    {
        $db = $this->getConnectionWithData();

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testTransactionShortcutCustom(): void
    {
        $db = $this->getConnectionWithData();

        $result = $db->transaction(
            static function (ConnectionPDOInterface $db): bool {
                $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
                return true;
            },
            TransactionInterface::READ_UNCOMMITTED,
        );

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            <<<SQL
            SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut'
            SQL,
        )->queryScalar();

        $this->assertSame('1', $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()->getAttribute(PDO::ATTR_ERRMODE));
    }
}
