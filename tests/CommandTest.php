<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\Common\CommonCommandTest;

use function trim;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAddColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addColumn('table', 'column', Schema::TYPE_INTEGER)->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [table] ADD [column] int
            SQL,
            $sql,
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddDropCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableName = 'test_ck';
        $name = 'test_ck_constraint';

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer'])->execute();

        $this->assertEmpty($schema->getTableChecks($tableName, true));

        $command->addCheck($name, $tableName, '[[int1]] > 1')->execute();

        $this->assertMatchesRegularExpression(
            '/^.*int1.*>.*1.*$/',
            $schema->getTableChecks($tableName, true)[0]->getExpression()
        );

        $command->dropCheck($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableChecks($tableName, true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAlterColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->alterColumn('table', 'column', Schema::TYPE_INTEGER)->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [table] ALTER COLUMN [column] int
            SQL,
            $sql,
        );
    }

    public function testAlterTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('testAlterTable', true) !== null) {
            $command->dropTable('testAlterTable')->execute();
        }

        $command->createTable('testAlterTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER])->execute();
        $command->insert('testAlterTable', ['bar' => 1])->execute();
        $command->alterColumn('testAlterTable', 'bar', Schema::TYPE_STRING)->execute();
        $command->insert('testAlterTable', ['bar' => 'hello'])->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[id]], [[bar]] FROM {{testAlterTable}};
            SQL
        )->queryAll();

        $this->assertSame([['id' => '1', 'bar' => '1'], ['id' => '2', 'bar' => 'hello']], $records);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAutoQuoting(): void
    {
        $db = $this->getConnection();

        $sql = <<<SQL
        SELECT [[id]], [[t.name]] FROM {{customer}} t
        SQL;
        $command = $db->createCommand($sql);

        $this->assertSame(
            <<<SQL
            SELECT [id], [t].[name] FROM [customer] t
            SQL,
            $command->getSql(),
        );
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::batchInsertSql()
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testBatchInsertSQL(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsertSQL($table, $columns, $values, $expected, $expectedParams, $insertedRow);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::bindParamsNonWhere()
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        parent::testBindParamsNonWhere($sql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBindParamValue(): void
    {
        parent::testBindParamValue();

        $db = $this->getConnection();

        $command = $db->createCommand();
        $command = $command->setSql(
            <<<SQL
            INSERT INTO type (int_col, char_col, float_col, blob_col, numeric_col, bool_col) VALUES (:int_col, :char_col, :float_col, CONVERT([varbinary], :blob_col), :numeric_col, :bool_col)
            SQL
        );
        $intCol = 123;
        $charCol = 'abc';
        $floatCol = 1.23;
        $blobCol = "\x10\x11\x12";
        $numericCol = '1.23';
        $boolCol = false;
        $command->bindParam(':int_col', $intCol);
        $command->bindParam(':char_col', $charCol);
        $command->bindParam(':float_col', $floatCol);
        $command->bindParam(':blob_col', $blobCol);
        $command->bindParam(':numeric_col', $numericCol);
        $command->bindParam(':bool_col', $boolCol);

        $this->assertEquals(1, $command->execute());

        $row = $command->setSql(
            <<<SQL
            SELECT int_col, char_col, float_col, CONVERT([nvarchar], blob_col) AS blob_col, numeric_col FROM type
            SQL
        )->queryOne();

        $this->assertIsArray($row);
        $this->assertEquals($intCol, $row['int_col']);
        $this->assertSame($charCol, trim($row['char_col']));
        $this->assertEquals($floatCol, (float) $row['float_col']);
        $this->assertSame($blobCol, $row['blob_col']);
        $this->assertEquals($numericCol, $row['numeric_col']);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::createIndex()
     *
     * @throws Exception
     */
    public function testCreateIndex(
        string $name,
        string $table,
        array|string $column,
        string $indexType,
        string $indexMethod,
        string $expected,
    ): void {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $sql = $command->createIndex($name, $table, $column, $indexType, $indexMethod)->getSql();

        $this->assertSame($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::rawSql()
     *
     * {@see https://github.com/yiisoft/yii2/issues/8592}
     *
     * @throws Exception
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::dataInsertVarbinary()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertVarbinary(mixed $expectedData, mixed $testData): void
    {
        $db = $this->getConnectionWithData();

        $db->createCommand()->delete('T_upsert_varbinary')->execute();
        $db->createCommand()->insert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $testData])->execute();
        $query = (new Query($db))->select(['blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();

        $this->assertIsArray($resultData);
        $this->assertSame($expectedData, $resultData['blob_col']);
    }

    /**
     * @throws Throwable
     */
    public function testInsertExWithComputedColumn(): void
    {
        $db = $this->getConnectionWithData();

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
            ALTER TABLE [dbo].[test_trigger] ADD [computed_column] AS dbo.TESTFUNC([ID])
            SQL
        )->execute();
        $command->setSql(
            <<<SQL
            ALTER TABLE [dbo].[test_trigger] ADD [RV] rowversion NULL
            SQL
        )->execute();
        $insertedString = 'test';
        $transaction = $db->beginTransaction();
        $result = $command->insertEx('test_trigger', ['stringcol' => $insertedString]);
        $transaction->commit();

        $this->assertIsArray($result);
        $this->assertSame($insertedString, $result['stringcol']);
        $this->assertSame('1', $result['id']);
    }

    /**
     * @throws Throwable
     */
    public function testInsertExWithRowVersionColumn(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            ALTER TABLE [dbo].[test_trigger] ADD [RV] rowversion
            SQL
        )->execute();
        $insertedString = 'test';
        $result = $command->insertEx('test_trigger', ['stringcol' => $insertedString]);

        $this->assertIsArray($result);
        $this->assertSame($insertedString, $result['stringcol']);
        $this->assertSame('1', $result['id']);
    }

    /**
     * @throws Throwable
     */
    public function testInsertExWithRowVersionNullColumn(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            ALTER TABLE [dbo].[test_trigger] ADD [RV] rowversion NULL
            SQL
        )->execute();
        $insertedString = 'test';
        $result = $command->insertEx(
            'test_trigger',
            ['stringcol' => $insertedString, 'RV' => new Expression('DEFAULT')],
        );

        $this->assertIsArray($result);
        $this->assertSame($insertedString, $result['stringcol']);
        $this->assertSame('1', $result['id']);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testRenameColumn(): void
    {
        $db = $this->getConnection();

        $sql = $db->createCommand()->renameColumn('table', 'oldname', 'newname')->getSql();

        $this->assertSame(
            <<<SQL
            sp_rename [table].[oldname], [newname] COLUMN
            SQL,
            $sql,
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testResetSequence(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $oldRow = $command->insertEx('item', ['name' => 'insert_value_for_sequence', 'category_id' => 1]);
        $command->delete('item', ['id' => $oldRow['id']])->execute();
        $command->resetSequence('item')->execute();
        $newRow = $command->insertEx('item', ['name' => 'insert_value_for_sequence', 'category_id' => 1]);

        $this->assertEquals($oldRow['id'], $newRow['id']);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::update()
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        string $expected
    ): void {
        parent::testUpdate($table, $columns, $conditions, $params, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::upsert()
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }
}
