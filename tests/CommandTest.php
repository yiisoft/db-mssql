<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractCommandTest;

use function str_starts_with;
use function trim;

/**
 * @group mssql
 */
final class CommandTest extends AbstractCommandTest
{
    use TestTrait;

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

    public function testAutoQuoting(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT [[id]], [[t.name]] FROM {{customer}} t';
        $command = $db->createCommand($sql);

        $this->assertSame('SELECT [id], [t].[name] FROM [customer] t', $command->getSql());
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::batchInsertSql()
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
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $sql = str_starts_with($table, '{{') ? "DELETE FROM $table" : 'DELETE FROM ' . $quoter->quoteTableName($table);
        $db->createCommand($sql)->execute();
        $command = $db->createCommand();
        $command->batchInsert($table, $columns, $values);
        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());

        $command->execute();

        $this->assertSame($insertedRow, (new Query($db))->from($table)->count());
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::bindParamsNonWhere
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

        $this->assertSame('Params', $command->queryScalar());
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

        $this->assertSame($name, $command->queryScalar());

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

        $this->assertSame(1, $command->execute());

        $sql = 'SELECT int_col, char_col, float_col, CONVERT([nvarchar], blob_col) AS blob_col, numeric_col FROM type';
        $row = $db->createCommand($sql)->queryOne();

        $this->assertIsArray($row);
        $this->assertEquals($intCol, $row['int_col']);
        $this->assertSame($charCol, trim($row['char_col']));
        $this->assertEquals($floatCol, (float) $row['float_col']);
        $this->assertSame($blobCol, $row['blob_col']);
        $this->assertSame($numericCol, $row['numeric_col']);

        // bindValue
        $sql = 'INSERT INTO customer(email, name, address) VALUES (:email, \'user5\', \'address5\')';
        $command = $db->createCommand($sql);
        $command->bindValue(':email', 'user5@example.com');
        $command->execute();
        $sql = 'SELECT email FROM customer WHERE name=:name';
        $command = $db->createCommand($sql);
        $command->bindValue(':name', 'user5');

        $this->assertSame('user5@example.com', $command->queryScalar());
    }

    /**
     * Test command getRawSql.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::rawSql()
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

        $this->assertSame($expectedRawSql, $command->getRawSql());
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with wrong query object.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::invalidSelectColumn()
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
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::dataInsertVarbinary()
     *
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public function testInsertVarbinary($expectedData, $testData)
    {
        $db = $this->getConnection();

        $db->createCommand()->delete('T_upsert_varbinary')->execute();
        $db->createCommand()->insert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $testData])->execute();
        $query = (new Query($db))->select(['blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();

        $this->assertIsArray($resultData);
        $this->assertSame($expectedData, $resultData['blob_col']);
    }

    /**
     * Test command upsert.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::upsert()
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{T_upsert}}')->execute();

        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $firstData);

        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $secondData);
    }
}
