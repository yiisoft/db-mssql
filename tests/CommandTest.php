<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\IndexType;
use Yiisoft\Db\Mssql\Tests\Provider\CommandProvider;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Common\CommonCommandTest;

use function array_filter;
use function trim;

/**
 * @group mssql
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    public function testAlterColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->alterColumn('{{customer}}', 'email', 'ntext')->execute();
        $schema = $db->getSchema();
        $columns = $schema->getTableSchema('{{customer}}')?->getColumns();

        $this->assertArrayHasKey('email', $columns);
        $this->assertSame('ntext', $columns['email']->getDbType());
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->checkIntegrity('{{dbo}}', '{{customer}}');

        $this->assertSame(
            <<<SQL
            ALTER TABLE [dbo].[customer] CHECK CONSTRAINT ALL;
            SQL,
            trim($command->getSql()),
        );
        $this->assertSame(0, $command->execute());
    }

    public function testCheckIntegrityExecuteException(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->checkIntegrity('{{dbo}}', '{{T_constraints_3}}', false)->execute();
        $sql = <<<SQL
        INSERT INTO [[T_constraints_3]] ([[C_id]], [[C_fk_id_1]], [[C_fk_id_2]]) VALUES (1, 2, 3)
        SQL;
        $command->setSql($sql)->execute();
        $db->createCommand()->checkIntegrity('{{dbo}}', '{{T_constraints_3}}')->execute();

        $this->expectException(IntegrityException::class);

        $command->setSql($sql)->execute();
    }

    #[DataProviderExternal(CommandProvider::class, 'rawSql')]
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    #[DataProviderExternal(CommandProvider::class, 'dataInsertVarbinary')]
    public function testInsertVarbinary(mixed $expectedData, mixed $testData): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->delete('{{T_upsert_varbinary}}')->execute();
        $command->insert('{{T_upsert_varbinary}}', ['id' => 1, 'blob_col' => $testData])->execute();
        $query = (new Query($db))->select(['{{blob_col}}'])->from('{{T_upsert_varbinary}}')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();

        $this->assertIsArray($resultData);
        $this->assertSame($expectedData, $resultData['blob_col']);
    }

    public function testInsertWithReturningPksWithComputedColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            CREATE OR ALTER FUNCTION TESTFUNC(@Number INT)
                RETURNS VARCHAR(15)
            AS
            BEGIN
                RETURN (SELECT TRY_CONVERT(VARCHAR(15),@Number))
            END
            SQL
        )->execute();
        $command->setSql(
            <<<SQL
            ALTER TABLE [[dbo]].[[test_trigger]] ADD [[computed_column]] AS dbo.TESTFUNC([[ID]])
            SQL
        )->execute();
        $command->setSql(
            <<<SQL
            ALTER TABLE [[dbo]].[[test_trigger]] ADD [[RV]] rowversion NULL
            SQL
        )->execute();
        $insertedString = 'test';
        $transaction = $db->beginTransaction();
        $result = $command->insertWithReturningPks('{{test_trigger}}', ['stringcol' => $insertedString]);
        $transaction->commit();

        $this->assertSame(['id' => '1'], $result);
    }

    public function testInsertWithReturningPksWithRowVersionColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            ALTER TABLE [[dbo]].[[test_trigger]] ADD [[RV]] rowversion
            SQL
        )->execute();
        $insertedString = 'test';
        $result = $command->insertWithReturningPks('{{test_trigger}}', ['stringcol' => $insertedString]);

        $this->assertSame(['id' => '1'], $result);
    }

    public function testInsertWithReturningPksWithRowVersionNullColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            ALTER TABLE [[dbo]].[[test_trigger]] ADD [[RV]] rowversion NULL
            SQL
        )->execute();
        $insertedString = 'test';
        $result = $command->insertWithReturningPks(
            '{{test_trigger}}',
            ['stringcol' => $insertedString, 'RV' => new Expression('DEFAULT')],
        );

        $this->assertSame(['id' => '1'], $result);
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $oldRow = $command->insertWithReturningPks('{{item}}', ['name' => 'insert_value_for_sequence', 'category_id' => 1]);
        $command->delete('{{item}}', ['id' => $oldRow['id']])->execute();
        $command->resetSequence('{{item}}')->execute();
        $newRow = $command->insertWithReturningPks('{{item}}', ['name' => 'insert_value_for_sequence', 'category_id' => 1]);

        $this->assertEquals($oldRow['id'], $newRow['id']);
    }

    #[DataProviderExternal(CommandProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        array $expectedValues,
        int $expectedCount,
    ): void {
        parent::testUpdate($table, $columns, $conditions, $params, $expectedValues, $expectedCount);
    }

    #[DataProviderExternal(CommandProvider::class, 'upsert')]
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }

    public function testQueryScalarWithBlob(): void
    {
        $db = $this->getConnection(true);

        $value = json_encode(['test'], JSON_THROW_ON_ERROR);
        $db->createCommand()->insert('{{%T_upsert_varbinary}}', ['id' => 1, 'blob_col' => $value])->execute();

        $scalarValue = $db->createCommand('SELECT [[blob_col]] FROM {{%T_upsert_varbinary}}')->queryScalar();
        $this->assertEquals($value, $scalarValue);
    }

    public function testAddDefaultValueSql(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->assertEmpty($command->getRawSql());
        $command->addDefaultValue('{{test_def}}', '{{test_def_constraint}}', 'int1', 41);
        $this->assertEquals(
            'ALTER TABLE [test_def] ADD CONSTRAINT [test_def_constraint] DEFAULT 41 FOR [int1]',
            $command->getRawSql()
        );
    }

    public function testDropDefaultValueSql(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->assertEmpty($command->getRawSql());
        $command->dropDefaultValue('{{test_def}}', '{{test_def_constraint}}');
        $this->assertEquals(
            'ALTER TABLE [test_def] DROP CONSTRAINT [test_def_constraint]',
            $command->getRawSql()
        );
    }

    public function testDropTableCascade(): void
    {
        $command = $this->getConnection()->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('MSSQL doesn\'t support cascade drop table.');
        $command->dropTable('{{table}}', cascade: true);
    }

    public function testShowDatabases(): void
    {
        $expectedDatabases = [];
        if (self::getDatabaseName() !== 'tempdb') {
            $expectedDatabases[] = self::getDatabaseName();
        }

        $actualDatabases = self::getDb()->createCommand()->showDatabases();

        $this->assertSame($expectedDatabases, $actualDatabases);
    }

    /** @link https://github.com/yiisoft/db-migration/issues/11 */
    public function testAlterColumnWithDefaultNull()
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        if ($db->getTableSchema('column_with_constraint', true) !== null) {
            $command->dropTable('column_with_constraint')->execute();
        }

        $command->createTable('column_with_constraint', ['id' => 'pk'])->execute();
        $command->addColumn('column_with_constraint', 'field', ColumnBuilder::integer()->null())->execute();
        $command->alterColumn('column_with_constraint', 'field', ColumnBuilder::string(40)->notNull())->execute();

        $fieldCol = $db->getTableSchema('column_with_constraint', true)->getColumn('field');

        $this->assertTrue($fieldCol->isNotNull());
        $this->assertNull($fieldCol->getDefaultValue());
        $this->assertSame('nvarchar', $fieldCol->getDbType());
        $this->assertSame(40, $fieldCol->getSize());

        $command->dropTable('column_with_constraint');
    }

    #[DataProviderExternal(CommandProvider::class, 'createIndex')]
    public function testCreateIndex(array $columns, array $indexColumns, string|null $indexType, string|null $indexMethod): void
    {
        parent::testCreateIndex($columns, $indexColumns, $indexType, $indexMethod);
    }

    public function createCreateClusteredColumnstoreIndex()
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $tableName = 'test_create_index';
        $indexName = 'test_index_name';

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['col1' => ColumnBuilder::integer()])->execute();
        $command->createIndex($tableName, $indexName, '', IndexType::CLUSTERED_COLUMNSTORE)->execute();

        $this->assertCount(1, $schema->getTableIndexes($tableName));

        $index = $schema->getTableIndexes($tableName)[0];

        $this->assertSame(['col1'], $index->getColumnNames());
        $this->assertFalse($index->isUnique());
        $this->assertFalse($index->isPrimary());

        $db->close();
    }

    public function testCreateXmlIndex(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $schema = $db->getSchema();

        $tableName = 'test_create_index';
        $primaryXmlIndexName = 'test_index_name';
        $xmlIndexName = 'test_index_name_xml';
        $indexColumns = ['col1'];

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['id' => ColumnBuilder::primaryKey(), 'col1' => 'xml'])->execute();
        $command->createIndex($tableName, $primaryXmlIndexName, $indexColumns, IndexType::PRIMARY_XML)->execute();
        $command->createIndex($tableName, $xmlIndexName, $indexColumns, IndexType::XML, "XML INDEX $primaryXmlIndexName FOR PATH")->execute();

        $this->assertCount(3, $schema->getTableIndexes($tableName));

        $index = array_filter($schema->getTableIndexes($tableName), static fn ($index) => !$index->isPrimary())[1];

        $this->assertSame($indexColumns, $index->getColumnNames());
        $this->assertFalse($index->isUnique());

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $values,
        array $columns,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsert($table, $values, $columns, $expected, $expectedParams, $insertedRow);
    }
}
