<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\AbstractCommandTest;
use Yiisoft\Db\Tests\Common\CommonCommandTest;
use Yiisoft\Db\Tests\Support\DbHelper;

use function trim;

/**
 * @group sqlite
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::batchInsert()
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1,
        string $fixture = 'type'
    ): void {
        parent::testBatchInsert($table, $columns, $values, $expected, $expectedParams, $insertedRow, $fixture);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->checkIntegrity('dbo', 'customer');

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
        $db = $this->getConnection('t_constraints');

        $command = $db->createCommand();
        $command->checkIntegrity('{{dbo}}', '{{T_constraints_3}}', false)->execute();
        $sql = <<<SQL
        INSERT INTO {{T_constraints_3}} ([[C_id]], [[C_fk_id_1]], [[C_fk_id_2]]) VALUES (1, 2, 3)
        SQL;
        $command->setSql($sql)->execute();
        $db->createCommand()->checkIntegrity('{{dbo}}', '{{T_constraints_3}}')->execute();

        $this->expectException(IntegrityException::class);

        $command->setSql($sql)->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::dataInsertVarbinary()
     */
    public function testInsertVarbinary(mixed $expectedData, mixed $testData): void
    {
        $db = $this->getConnection('t_upsert_varbinary');

        $command = $db->createCommand();

        $command->delete('T_upsert_varbinary')->execute();
        $command->insert('T_upsert_varbinary', ['id' => 1, 'blob_col' => $testData])->execute();
        $query = (new Query($db))->select(['blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();

        $this->assertIsArray($resultData);
        $this->assertSame($expectedData, $resultData['blob_col']);
    }

    public function testInsertExWithComputedColumn(): void
    {
        $db = $this->getConnection('test_trigger');

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

    public function testInsertExWithRowVersionColumn(): void
    {
        $db = $this->getConnection('test_trigger');

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

    public function testInsertExWithRowVersionNullColumn(): void
    {
        $db = $this->getConnection('test_trigger');

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

    public function testResetSequence(): void
    {
        $db = $this->getConnection('item');

        $command = $db->createCommand();
        $oldRow = $command->insertEx('item', ['name' => 'insert_value_for_sequence', 'category_id' => 1]);
        $command->delete('item', ['id' => $oldRow['id']])->execute();
        $command->resetSequence('item')->execute();
        $newRow = $command->insertEx('item', ['name' => 'insert_value_for_sequence', 'category_id' => 1]);

        $this->assertEquals($oldRow['id'], $newRow['id']);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandProvider::update()
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
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }
}
