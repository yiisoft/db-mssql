<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Mssql\PDO\SchemaPDOMssql;
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
            ['id' => SchemaPDOMssql::TYPE_PK, 'bar' => SchemaPDOMssql::TYPE_INTEGER]
        )->execute();

        $db->createCommand()->insert('testAlterTable', ['bar' => 1])->execute();
        $db->createCommand()->alterColumn('testAlterTable', 'bar', SchemaPDOMssql::TYPE_STRING)->execute();
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

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::bindParamsNonWhereProvider
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
        $params = [':email' => 'testParams@example.com', ':len' => 5];
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
        $command->insert('{{customer}}', $query)->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::dataInsertVarbinary
     *
     * @throws \Throwable
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
        $this->assertEquals($expectedData, $resultData['blob_col']);
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
}
