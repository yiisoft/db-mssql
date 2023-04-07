<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\TableSchemaInterface;

/**
 * @link https://learn.microsoft.com/en-us/sql/t-sql/statements/create-table-transact-sql?view=sql-server-ver16#examples
 */
final class CreateTableTest extends TestCase
{
    use TestTrait;

    public function testCreatePrimarykeyIntegerConstraintOnAColumn(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('test_create_table') !== null) {
            $db->createCommand()->dropTable('test_create_table')->execute();
        }

        $command->createTable(
            'test_create_table',
            [
                'id' => $schema->createColumn(Schema::TYPE_INTEGER)->notNull()->primaryKey()->clustered(),
                'name' => $schema->createColumn(Schema::TYPE_TEXT)->notNull(),
            ]
        );

        $this->assertSame(
            <<<SQL
            CREATE TABLE [test_create_table] (
            \t[id] int NOT NULL PRIMARY KEY CLUSTERED,
            \t[name] nvarchar(max) NOT NULL
            )
            SQL,
            $command->getSql(),
        );

        $command->execute();

        $this->assertInstanceOf(TableSchemaInterface::class, $schema->getTableSchema('test_create_table'));
    }

    public function testCreatePrimarykeyIntegerCompositeFKOnAColumn(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('test_create_table') !== null) {
            $db->createCommand()->dropTable('test_create_table')->execute();
        }

        $command->createTable(
            'test_create_table',
            [
                'id' => $schema->createColumn(Schema::TYPE_INTEGER)->notNull(),
                'name' => $schema->createColumn(Schema::TYPE_STRING)->notNull(),
                'PRIMARY KEY (id, name)',
            ]
        );

        $this->assertSame(
            <<<SQL
            CREATE TABLE [test_create_table] (
            \t[id] int NOT NULL,
            \t[name] nvarchar(255) NOT NULL,
            \tPRIMARY KEY (id, name)
            )
            SQL,
            $command->getSql(),
        );

        $command->execute();


        $this->assertInstanceOf(TableSchemaInterface::class, $schema->getTableSchema('test_create_table'));
    }

    public function testShowTheCompleteTableDefinition(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('test_create_table') !== null) {
            $db->createCommand()->dropTable('test_create_table')->execute();
        }

        $command->createTable(
            'test_create_table',
            [
                'PurchaseOrderID' => $schema
                    ->createColumn(Schema::TYPE_INTEGER)
                    ->notNull()
                    ->append('REFERENCES PurchaseOrderHeader(PurchaseOrderID)'),
                'LineNumber' => $schema->createColumn(Schema::TYPE_SMALLINT)->notNull(),
                'productID' => $schema->createColumn(Schema::TYPE_INTEGER)
                    ->null()
                    ->append('REFERENCES Production.Product(ProductID)'),
                'UnitPrice' => $schema->createColumn(Schema::TYPE_MONEY)->null(),
                'OrderQty' => $schema->createColumn(Schema::TYPE_SMALLINT)->null(),
                'ReceivedQty' => $schema->createColumn(Schema::TYPE_FLOAT)->null(),
                'RejectedQty' => $schema->createColumn(Schema::TYPE_FLOAT)->null(),
                'DueDate' => $schema->createColumn(Schema::TYPE_DATETIME)->null(),
                'rowguid' => $schema
                    ->createColumn(Schema::TYPE_UUID)
                    ->append('ROWGUIDCOL NOT NULL CONSTRAINT DF_PurchaseOrderDetail_rowguid DEFAULT (NEWID())'),
            ]
        );

        $this->assertSame(
            <<<SQL
            CREATE TABLE [test_create_table] (
            \t[PurchaseOrderID] int NOT NULL REFERENCES PurchaseOrderHeader(PurchaseOrderID),
            \t[LineNumber] smallint NOT NULL,
            \t[productID] int NULL DEFAULT NULL REFERENCES Production.Product(ProductID),
            \t[UnitPrice] decimal(19,4) NULL DEFAULT NULL,
            \t[OrderQty] smallint NULL DEFAULT NULL,
            \t[ReceivedQty] float NULL DEFAULT NULL,
            \t[RejectedQty] float NULL DEFAULT NULL,
            \t[DueDate] datetime NULL DEFAULT NULL,
            \t[rowguid] UNIQUEIDENTIFIER ROWGUIDCOL NOT NULL CONSTRAINT DF_PurchaseOrderDetail_rowguid DEFAULT (NEWID())
            )
            SQL,
            $command->getSql(),
        );
    }
}
