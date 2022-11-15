<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use JsonException;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use TestTrait;

    /**
     * @throws Exception
     */
    public function testAddColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [user] ADD [age] int
            SQL,
            $qb->addColumn('user', 'age', 'integer')
        );
    }

    /**
     * @throws Exception
     */
    public function testAddCommentOnColumnException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->addCommentOnColumn('noExist', 'noExist', 'noExist');
    }

    /**
     * @throws Exception
     */
    public function testAddCommentOnTableException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->addCommentOnTable('noExist', 'noExist');
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::alterColumn()
     */
    public function testAlterColumn(
        string $table,
        string $column,
        ColumnSchemaBuilder|string $type,
        string $expected
    ): void {
        parent::testAlterColumn($table, $column, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::batchInsert()
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $value,
        string|null $expected,
        array $expectedParams = []
    ): void {
        parent::testBatchInsert($table, $columns, $value, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildConditions()
     *
     * @throws Exception
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $conditions,
        string $expected,
        array $expectedParams = []
    ): void {
        $this->getConnectionWithData();

        parent::testBuildCondition($conditions, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildLikeConditions()
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->limit(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
            SQL,
            $sql,
        );
        $this->assertSame([], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->offset(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * ORDER BY (SELECT NULL) OFFSET 10 ROWS
            SQL,
            $sql,
        );
        $this->assertSame([], $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildWhereExists()
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        parent::testBuildWhereExists($cond, $expectedQuerySql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::delete()
     */
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        parent::testDelete($table, $condition, $expectedSQL, $expectedParams);
    }

    /**
     * @throws Exception
     */
    public function testDropCommentFromColumnException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->dropCommentFromColumn('noExist', 'noExist');
    }

    /**
     * @throws Exception
     */
    public function testDropCommentFromTableException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->dropCommentFromTable('noExist');
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::insert()
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::insertEx()
     */
    public function testInsertEx(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsertEx($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @throws Exception
     */
    public function testRenameColumn(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();

        $sql = $qb->renameColumn('alpha', 'string_identifier', 'string_identifier_test');
        $this->assertSame('sp_rename [alpha].[string_identifier], [string_identifier_test] COLUMN', $sql);

        $sql = $qb->renameColumn('alpha', 'string_identifier_test', 'string_identifier');
        $this->assertSame('sp_rename [alpha].[string_identifier_test], [string_identifier] COLUMN', $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::selectExist()
     */
    public function testSelectExists(string $sql, string $expected): void
    {
        parent::testSelectExists($sql, $expected);
    }

    public function testResetSequence(): void
    {
        $qb = $this->getConnection(true)->getQueryBuilder();

        $sql = $qb->resetSequence('item');
        $this->assertSame("DBCC CHECKIDENT ('[item]', RESEED, 0) WITH NO_INFOMSGS;DBCC CHECKIDENT ('[item]', RESEED)", $sql);

        $sql = $qb->resetSequence('item', 4);
        $this->assertSame("DBCC CHECKIDENT ('[item]', RESEED, 4)", $sql);

        $sql = $qb->resetSequence('item', '1');
        $this->assertSame("DBCC CHECKIDENT ('[item]', RESEED, 1)", $sql);
    }

    public function testResetSequenceException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is not sequence associated with table 'noExist'.");

        $sql = $this->getConnection(true)->getQueryBuilder()->resetSequence('noExist');
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::update()
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::upsert()
     *
     * @throws Exception
     * @throws JsonException
     * @throws NotSupportedException|
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array|string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws JsonException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpsertVarbinary(): void
    {
        $db = $this->getConnectionWithData();

        $qb = $db->getQueryBuilder();
        $params = [];
        $expected = json_encode(['test' => 'string', 'test2' => 'integer']);
        $sql = $qb->upsert(
            'T_upsert_varbinary',
            ['id' => 1, 'blob_col' => $expected],
            ['blob_col' => $expected],
            $params,
        );
        $execute = $db->createCommand($sql, $params)->execute();

        $this->assertSame(1, $execute);

        $query = (new Query($db))->select(['blob_col as blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $data = $query->createCommand()->queryOne();

        $this->assertIsArray($data);
        $this->assertEquals($expected, $data['blob_col']);
    }
}
