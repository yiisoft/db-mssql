<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Throwable;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\TestSupport\TestCommandTrait;

use function trim;

/**
 * @group mssql
 * @group upsert
 */
final class CommandTest extends TestCase
{
    use TestCommandTrait;

    protected $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    public function testAddDropCheck(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        $tableName = 'test_ck';
        $name = 'test_ck_constraint';

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, ['int1' => 'integer'])->execute();
        $this->assertEmpty($schema->getTableChecks($tableName, true));

        $db->createCommand()->addCheck($name, $tableName, '[[int1]] > 1')->execute();
        $this->assertMatchesRegularExpression(
            '/^.*int1.*>.*1.*$/',
            $schema->getTableChecks($tableName, true)[0]->getExpression()
        );

        $db->createCommand()->dropCheck($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableChecks($tableName, true));
    }

    public function testAlterTable(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('testAlterTable') !== null) {
            $db->createCommand()->dropTable('testAlterTable')->execute();
        }

        $db->createCommand()->createTable(
            'testAlterTable',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER]
        )->execute();

        $db->createCommand()->insert('testAlterTable', ['bar' => 1])->execute();
        $db->createCommand()->alterColumn('testAlterTable', 'bar', Schema::TYPE_STRING)->execute();
        $db->createCommand()->insert('testAlterTable', ['bar' => 'hello'])->execute();
        $records = $db->createCommand('SELECT [[id]], [[bar]] FROM {{testAlterTable}};')->queryAll();
        $this->assertEquals([['id' => 1, 'bar' => 1], ['id' => 2, 'bar' => 'hello']], $records);
    }

    public function testAutoQuoting(): void
    {
        $db = $this->getConnection();
        $sql = 'SELECT [[id]], [[t.name]] FROM {{customer}} t';
        $command = $db->createCommand($sql);
        $this->assertEquals('SELECT [id], [t].[name] FROM [customer] t', $command->getSql());
    }

    public function batchInsertSqlProvider()
    {
        $data = $this->batchInsertSqlProviderTrait();

        $data['multirow']['expectedParams'][':qp1'] = '0.0';
        $data['multirow']['expectedParams'][':qp3'] = 1;
        $data['multirow']['expectedParams'][':qp5'] = '0';
        $data['multirow']['expectedParams'][':qp7'] = 0;

        $data['issue11242']['expectedParams'][':qp1'] = '1.1';
        $data['issue11242']['expectedParams'][':qp3'] = 1;

        $data['wrongBehavior']['expectedParams'][':qp1'] = '0.0';
        $data['wrongBehavior']['expectedParams'][':qp3'] = 0;

        $data['batchInsert binds params from expression']['expectedParams'][':qp1'] = '1';
        $data['batchInsert binds params from expression']['expectedParams'][':qp3'] = 0;

        return $data;
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider batchInsertSqlProvider
     *
     * @param string $table
     * @param array $columns
     * @param array $values
     * @param string $expected
     * @param array $expectedParams
     * @param int $insertedRow
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsertSQL(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->batchInsert($table, $columns, $values);

        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());

        $command->execute();
        $this->assertEquals($insertedRow, (new Query($db))->from($table)->count());
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::bindParamsNonWhereProvider
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getConnection();
        $db->createCommand()->insert(
            'customer',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1',
            ]
        )->execute();
        $params = [':email' => 'testParams@example.com', ':len' => 5];
        $command = $db->createCommand($sql, $params);
        $this->assertEquals('Params', $command->queryScalar());
    }

    public function testBindParamValue(): void
    {
        $db = $this->getConnection(true);

        // bindParam
        $sql = 'INSERT INTO customer(email, name, address) VALUES (:email, :name, :address)';
        $command = $db->createCommand($sql);
        $email = 'user4@example.com';
        $name = 'user4';
        $address = 'address4';
        $command->bindParam(':email', $email);
        $command->bindParam(':name', $name);
        $command->bindParam(':address', $address);
        $command->execute();

        $sql = 'SELECT name FROM customer WHERE email=:email';
        $command = $db->createCommand($sql);
        $command->bindParam(':email', $email);
        $this->assertEquals($name, $command->queryScalar());

        $sql = 'INSERT INTO type (int_col, char_col, float_col, blob_col, numeric_col, bool_col)
            VALUES (:int_col, :char_col, :float_col, CONVERT([varbinary], :blob_col), :numeric_col, :bool_col)';
        $command = $db->createCommand($sql);
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

        $sql = 'SELECT int_col, char_col, float_col, CONVERT([nvarchar], blob_col) AS blob_col, numeric_col
            FROM type';
        $row = $db->createCommand($sql)->queryOne();
        $this->assertIsArray($row);
        $this->assertEquals($intCol, $row['int_col']);
        $this->assertEquals($charCol, trim($row['char_col']));
        $this->assertEquals($floatCol, (float) $row['float_col']);
        $this->assertEquals($blobCol, $row['blob_col']);
        $this->assertEquals($numericCol, $row['numeric_col']);

        // bindValue
        $sql = 'INSERT INTO customer(email, name, address) VALUES (:email, \'user5\', \'address5\')';
        $command = $db->createCommand($sql);
        $command->bindValue(':email', 'user5@example.com');
        $command->execute();
        $sql = 'SELECT email FROM customer WHERE name=:name';
        $command = $db->createCommand($sql);
        $command->bindValue(':name', 'user5');
        $this->assertEquals('user5@example.com', $command->queryScalar());
    }

    /**
     * Test command getRawSql.
     *
     * @dataProvider getRawSqlProviderTrait
     *
     * @param string $sql
     * @param array  $params
     * @param string $expectedRawSql
     *
     * {@see https://github.com/yiisoft/yii2/issues/8592}
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand($sql, $params);
        $this->assertEquals($expectedRawSql, $command->getRawSql());
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with wrong query object.
     *
     * @dataProvider invalidSelectColumnsProviderTrait
     */
    public function testInsertSelectFailed(mixed $invalidSelectColumns): void
    {
        $db = $this->getConnection();
        $query = new Query($db);
        $query->select($invalidSelectColumns)->from('{{customer}}');
        $command = $db->createCommand();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected select query object with enumerated (named) parameters');
        $command->insert('{{customer}}', $query)->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::dataInsertVarbinary
     *
     * @throws Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public function testInsertVarbinary($expectedData, $testData)
    {
        $db = $this->getConnection(true);
        $db->createCommand()->delete('T_upsert_varbinary')->execute();
        $db->createCommand()->insert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $testData])->execute();
        $query = (new Query($db))->select(['blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();
        $this->assertIsArray($resultData);
        $this->assertEquals($expectedData, $resultData['blob_col']);
    }

    /**
     * Test command upsert.
     *
     * @dataProvider upsertProviderTrait
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection(true);
        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $firstData);
        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $secondData);
    }

    /**
     * @throws Throwable
     */
    public function testInsertExWithComputedColumn(): void
    {
        $db = $this->getConnection(true);

        $sql = 'CREATE OR ALTER FUNCTION TESTFUNC(@Number INT)
RETURNS VARCHAR(15)
AS
BEGIN
      RETURN (SELECT TRY_CONVERT(VARCHAR(15),@Number))
END';
        $db->createCommand($sql)->execute();

        $sql = 'ALTER TABLE [dbo].[test_trigger] ADD [computed_column] AS dbo.TESTFUNC([ID])';
        $db->createCommand($sql)->execute();

        $insertedString = 'test';

        $transaction = $db->beginTransaction();
        $result = $db->createCommand()->insertEx('test_trigger', ['stringcol' => $insertedString]);
        $transaction->commit();

        $this->assertIsArray($result);
        $this->assertEquals($insertedString, $result['stringcol']);
        $this->assertEquals(1, $result['id']);
    }

    /**
     * @throws Throwable
     */
    public function testInsertExWithRowVersionColumn(): void
    {
        $db = $this->getConnection(true);

        $sql = 'ALTER TABLE [dbo].[test_trigger] ADD [RV] rowversion';
        $db->createCommand($sql)->execute();

        $insertedString = 'test';
        $result = $db->createCommand()->insertEx('test_trigger', ['stringcol' => $insertedString]);

        $this->assertIsArray($result);
        $this->assertEquals($insertedString, $result['stringcol']);
        $this->assertEquals(1, $result['id']);
    }

    /**
     * @throws Throwable
     */
    public function testInsertExWithRowVersionNullColumn(): void
    {
        $db = $this->getConnection(true);

        $sql = 'ALTER TABLE [dbo].[test_trigger] ADD [RV] rowversion NULL';
        $db->createCommand($sql)->execute();

        $insertedString = 'test';
        $result = $db->createCommand()->insertEx('test_trigger', ['stringcol' => $insertedString, 'RV' => new Expression('DEFAULT')]);

        $this->assertIsArray($result);
        $this->assertEquals($insertedString, $result['stringcol']);
        $this->assertEquals(1, $result['id']);
    }
}
