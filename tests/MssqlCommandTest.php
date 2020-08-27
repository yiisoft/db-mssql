<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Schema\MssqlSchema;
use Yiisoft\Db\TestUtility\TestCommandTrait;

use function trim;

/**
 * @group mssql
 */
final class MssqlCommandTest extends TestCase
{
    use TestCommandTrait;

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

    public function paramsNonWhereProvider(): array
    {
        return[
            ['SELECT SUBSTRING(name, :len, 6) AS name FROM {{customer}} WHERE [[email]] = :email GROUP BY name'],
            ['SELECT SUBSTRING(name, :len, 6) as name FROM {{customer}} WHERE [[email]] = :email ORDER BY name'],
            ['SELECT SUBSTRING(name, :len, 6) FROM {{customer}} WHERE [[email]] = :email'],
        ];
    }

    public function testAlterTable(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('testAlterTable') !== null) {
            $db->createCommand()->dropTable('testAlterTable')->execute();
        }

        $db->createCommand()->createTable(
            'testAlterTable',
            ['id' => MssqlSchema::TYPE_PK, 'bar' => MssqlSchema::TYPE_INTEGER]
        )->execute();

        $db->createCommand()->insert('testAlterTable', ['bar' => 1])->execute();

        $db->createCommand()->alterColumn('testAlterTable', 'bar', MssqlSchema::TYPE_STRING)->execute();

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

    public function batchInsertSqlProvider(): array
    {
        return [
            'issue11242' => [
                'type',
                ['int_col', 'float_col', 'char_col'],
                [['', '', 'Kyiv {{city}}, Ukraine']],

                /**
                 * {@see https://github.com/yiisoft/yii2/issues/11242}
                 *
                 * Make sure curly bracelets (`{{..}}`) in values will not be escaped
                 */
                'expected' => 'INSERT INTO [type] ([int_col], [float_col], [char_col])'
                    . ' VALUES (NULL, NULL, \'Kyiv {{city}}, Ukraine\')'
            ],
            'wrongBehavior' => [
                '{{%type}}',
                ['{{%type}}.[[int_col]]', '[[float_col]]', 'char_col'],
                [['', '', 'Kyiv {{city}}, Ukraine']],

                /**
                 * Test covers potentially wrong behavior and marks it as expected!.
                 *
                 * In case table name or table column is passed with curly or square bracelets, QueryBuilder can not
                 * determine the table schema and typecast values properly.
                 *
                 * TODO: make it work. Impossible without BC breaking for public methods.
                 */
                'expected' => 'INSERT INTO [type] ([type].[int_col], [float_col], [char_col])'
                    . ' VALUES (\'\', \'\', \'Kyiv {{city}}, Ukraine\')'
            ],
            'batchInsert binds params from expression' => [
                '{{%type}}',
                ['int_col'],

                /**
                 * This example is completely useless. This feature of batchInsert is intended to be used with complex
                 * expression objects, such as JsonExpression.
                 */
                [[new Expression(':qp1', [':qp1' => 42])]],
                'expected'       => 'INSERT INTO [type] ([int_col]) VALUES (:qp1)',
                'expectedParams' => [':qp1' => 42]
            ]
        ];
    }
}
