<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Yiisoft\Db\Pdo\PdoValue;
use function trim;
use yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Query\Query;

use Yiisoft\Db\TestUtility\TestCommandTrait;

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

        $tableName = 'test_ck';
        $name = 'test_ck_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer',
        ])->execute();

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

        $this->assertEquals('SELECT [id], [t].[name] FROM [customer] t', $command->getSql());
    }

    public function testBindParamValue(): void
    {
        $db = $this->getConnection(true);

        /* bindParam */
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

        $this->assertEquals($intCol, $row['int_col']);
        $this->assertEquals($charCol, trim($row['char_col']));
        $this->assertEquals($floatCol, (float) $row['float_col']);
        $this->assertEquals($blobCol, $row['blob_col']);
        $this->assertEquals($numericCol, $row['numeric_col']);

        /* bindValue */
        $sql = 'INSERT INTO customer(email, name, address) VALUES (:email, \'user5\', \'address5\')';

        $command = $db->createCommand($sql);

        $command->bindValue(':email', 'user5@example.com');

        $command->execute();

        $sql = 'SELECT email FROM customer WHERE name=:name';

        $command = $db->createCommand($sql);

        $command->bindValue(':name', 'user5');

        $this->assertEquals('user5@example.com', $command->queryScalar());
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

        $this->assertEquals([
            ['id' => 1, 'bar' => 1],
            ['id' => 2, 'bar' => 'hello'],
        ], $records);
    }

    public function testAddDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_def';
        $name = 'test_def_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer',
        ])->execute();

        $this->assertEmpty($schema->getTableDefaultValues($tableName, true));

        $db->createCommand()->addDefaultValue($name, $tableName, 'int1', 41)->execute();

        $this-> assertMatchesRegularExpression(
            '/^.*41.*$/',
            $schema->getTableDefaultValues($tableName, true)[0]->getValue()
        );

        $db->createCommand()->dropDefaultValue($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableDefaultValues($tableName, true));
    }

    public function batchInsertSqlProvider()
    {
        $data = $this->batchInsertSqlProviderTrait();

        $data['issue11242']['expected'] = 'INSERT INTO [type] ([int_col], [float_col], [char_col])'
            . ' VALUES (NULL, NULL, \'Kyiv {{city}}, Ukraine\')';

        $data['wrongBehavior']['expected'] = 'INSERT INTO [type] ([type].[int_col], [float_col], [char_col])'
            . ' VALUES (\'\', \'\', \'Kyiv {{city}}, Ukraine\')';

        $data['batchInsert binds params from expression']['expected'] = 'INSERT INTO [type] ([int_col]) VALUES (:qp1)';

        unset($data['batchIsert empty rows represented by ArrayObject']);

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
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsertSQL(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = []
    ): void {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->batchInsert($table, $columns, $values);

        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());
    }

    public function bindParamsNonWhereProvider(): array
    {
        return[
            ['SELECT SUBSTRING(name, :len, 6) AS name FROM {{customer}} WHERE [[email]] = :email GROUP BY name'],
            ['SELECT SUBSTRING(name, :len, 6) as name FROM {{customer}} WHERE [[email]] = :email ORDER BY name'],
            ['SELECT SUBSTRING(name, :len, 6) FROM {{customer}} WHERE [[email]] = :email'],
        ];
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider bindParamsNonWhereProvider
     *
     * @param string $sql
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

        $params = [
            ':email' => 'testParams@example.com',
            ':len' => 5,
        ];

        $command = $db->createCommand($sql, $params);

        $this->assertEquals('Params', $command->queryScalar());
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
     *
     * @param mixed $invalidSelectColumns
     */
    public function testInsertSelectFailed($invalidSelectColumns): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $query->select($invalidSelectColumns)->from('{{customer}}');

        $command = $db->createCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected select query object with enumerated (named) parameters');

        $command->insert(
            '{{customer}}',
            $query
        )->execute();
    }

    public function testInsertVarbinary()
    {
        $db = $this->getConnection();

        $testData = json_encode(['string' => 'string', 'integer' => 1234]);

        $db->createCommand()->insert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $testData])->execute();

        $query = (new Query($db))
            ->select(['convert(varchar(max),blob_col) as blob_col'])
            ->from('T_upsert_varbinary')
            ->where(['id' => 1]);

        $resultData = $query->createCommand()->queryOne();
        $this->assertEquals($testData, $resultData['blob_col']);
    }

    public function testInsertPdoLobToVarbinary()
    {
        $db = $this->getConnection();

        $testData = serialize(['string' => 'string', 'integer' => 1234]);
        $value = new PdoValue($testData, PDO::PARAM_LOB);

        $db->createCommand()->insert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $value])->execute();

        $query = (new Query($db))
            ->select(['convert(varchar(max),blob_col) as blob_col'])
            ->from('T_upsert_varbinary')
            ->where(['id' => 1]);

        $resultData = $query->createCommand()->queryOne();

        $this->assertEquals($testData, $resultData['blob_col']);
    }

    /**
     * Test command upsert.
     *
     * @dataProvider upsertProviderTrait
     *
     * @param array $firstData
     * @param array $secondData
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection(true);

        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());

        $this->performAndCompareUpsertResult($db, $firstData);

        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());

        $this->performAndCompareUpsertResult($db, $secondData);
    }

    public function testUpsertVarbinary()
    {
        $db = $this->getConnection();

        $testData = json_encode(['test' => 'string', 'test2' => 'integer']);
        $params = [];

        $qb = $db->getQueryBuilder();
        $sql = $qb->upsert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $testData], ['blob_col' => $testData], $params);

        $result = $db->createCommand($sql, $params)->execute();

        $this->assertEquals(1, $result);

        $query = (new Query($db))
            ->select(['convert(varchar(max),blob_col) as blob_col'])
            ->from('T_upsert_varbinary')
            ->where(['id' => 1]);

        $resultData = $query->createCommand()->queryOne();
        $this->assertEquals($testData, $resultData['blob_col']);
    }
}
