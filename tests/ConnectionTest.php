<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Column\ColumnFactory;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionTest;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group mssql
 */
final class ConnectionTest extends CommonConnectionTest
{
    use TestTrait;

    public function testTransactionIsolation(): void
    {
        $db = $this->getConnection(true);

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
            static function (PdoConnectionInterface $db): bool {
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

    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()?->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function getColumnBuilderClass(): void
    {
        $db = $this->getConnection();

        $this->assertSame(ColumnBuilder::class, $db->getColumnBuilderClass());

        $db->close();
    }

    public function testGetColumnFactory(): void
    {
        $db = $this->getConnection();

        $this->assertInstanceOf(ColumnFactory::class, $db->getColumnFactory());

        $db->close();
    }

    public function testUserDefinedColumnFactory(): void
    {
        $columnFactory = new ColumnFactory();

        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache(), $columnFactory);

        $this->assertSame($columnFactory, $db->getColumnFactory());

        $db->close();
    }
}
