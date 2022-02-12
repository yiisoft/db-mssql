<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Closure;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\PDO\QueryBuilderPDOMssql;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\TestSupport\TestQueryBuilderTrait;
use Yiisoft\Db\TestSupport\TraversableObject;

use function array_replace;

/**
 * @group mssql
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::addDropChecksProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    public function testAddDropDefaultValue(): void
    {
        $tableName = 'test_def';
        $name = 'test_def_constraint';

        $db = $this->getConnection();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, ['int1' => 'integer'])->execute();
        $this->assertEmpty($schema->getTableDefaultValues($tableName, true));

        $db->createCommand()->addDefaultValue($name, $tableName, 'int1', 41)->execute();
        $this-> assertMatchesRegularExpression(
            '/^.*41.*$/',
            $schema->getTableDefaultValues($tableName, true)[0]->getValue()
        );

        $db->createCommand()->dropDefaultValue($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableDefaultValues($tableName, true));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::addDropForeignKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::addDropPrimaryKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::addDropUniquesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::batchInsertProvider
     *
     * @param string $table
     * @param array $columns
     * @param array $value
     * @param string $expected
     */
    public function testBatchInsert(string $table, array $columns, array $value, string $expected): void
    {
        $db = $this->getConnection();
        $sql = $db->getQueryBuilder()->batchInsert($table, $columns, $value);
        $this->assertEquals($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildConditionsProvider
     *
     * @param array|ExpressionInterface $condition
     * @param string $expected
     * @param array $expectedParams
     */
    public function testBuildCondition($condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildFilterConditionProvider
     *
     * @param array $condition
     * @param string $expected
     * @param array $expectedParams
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->filterWhere($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildFromDataProvider
     *
     * @param string $table
     * @param string $expected
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $db = $this->getConnection();
        $params = [];
        $sql = $db->getQueryBuilder()->buildFrom([$table], $params);
        $this->assertEquals('FROM ' . $this->replaceQuotes($expected), $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildLikeConditionsProvider
     *
     * @param array|object $condition
     * @param string $expected
     * @param array $expectedParams
     */
    public function testBuildLikeCondition($condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildExistsParamsProvider
     *
     * @param string $cond
     * @param string $expectedQuerySql
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection();
        $expectedQueryParams = [];
        $subQuery = new Query($db);
        $subQuery->select('1')->from('Website w');
        $query = new Query($db);
        $query->select('id')->from('TotalExample t')->where([$cond, $subQuery]);
        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testCommentAdditionOnQuotedTableOrColumn(): void
    {
        $table = 'stranger \'table';
        $tableComment = 'A comment for stranger \'table.';
        $this->runAddCommentOnTable($tableComment, $table);

        $resultTable = $this->getCommmentsFromTable($table);
        $this->assertEquals([
            'objtype' => 'TABLE',
            'objname' => $table,
            'name' => 'MS_description',
            'value' => $tableComment,
        ], $resultTable[0]);

        $column = 'stranger \'field';
        $columnComment = 'A comment for stranger \'field column in stranger \'table.';
        $this->runAddCommentOnColumn($columnComment, $table, $column);

        $resultColumn = $this->getCommentsFromColumn($table, $column);
        $this->assertEquals([
            'objtype' => 'COLUMN',
            'objname' => $column,
            'name' => 'MS_description',
            'value' => $columnComment,
        ], $resultColumn[0]);
    }

    public function testCommentAdditionOnTableAndOnColumn(): void
    {
        $table = 'profile';
        $tableComment = 'A comment for profile table.';
        $this->runAddCommentOnTable($tableComment, $table);

        $resultTable = $this->getCommmentsFromTable($table);
        $this->assertEquals([
            'objtype' => 'TABLE',
            'objname' => $table,
            'name' => 'MS_description',
            'value' => $tableComment,
        ], $resultTable[0]);

        $column = 'description';
        $columnComment = 'A comment for description column in profile table.';

        $this->runAddCommentOnColumn($columnComment, $table, $column);

        $resultColumn = $this->getCommentsFromColumn($table, $column);

        $this->assertEquals([
            'objtype' => 'COLUMN',
            'objname' => $column,
            'name' => 'MS_description',
            'value' => $columnComment,
        ], $resultColumn[0]);

        /* Add another comment to the same table to test update */
        $tableComment2 = 'Another comment for profile table.';

        $this->runAddCommentOnTable($tableComment2, $table);

        $result = $this->getCommmentsFromTable($table);

        $this->assertEquals([
            'objtype' => 'TABLE',
            'objname' => $table,
            'name' => 'MS_description',
            'value' => $tableComment2,
        ], $result[0]);

        /* Add another comment to the same column to test update */
        $columnComment2 = 'Another comment for description column in profile table.';

        $this->runAddCommentOnColumn($columnComment2, $table, $column);

        $result = $this->getCommentsFromColumn($table, $column);

        $this->assertEquals([
            'objtype' => 'COLUMN',
            'objname' => $column,
            'name' => 'MS_description',
            'value' => $columnComment2,
        ], $result[0]);
    }

    public function testCommentRemovalFromTableAndFromColumn(): void
    {
        $table = 'profile';
        $tableComment = 'A comment for profile table.';
        $this->runAddCommentOnTable($tableComment, $table);
        $this->runDropCommentFromTable($table);

        $result = $this->getCommmentsFromTable($table);
        $this->assertEquals([], $result);

        $column = 'description';
        $columnComment = 'A comment for description column in profile table.';
        $this->runAddCommentOnColumn($columnComment, $table, $column);
        $this->runDropCommentFromColumn($table, $column);

        $result = $this->getCommentsFromColumn($table, $column);
        $this->assertEquals([], $result);
    }

    public function testCommentRemovalFromQuotedTableOrColumn(): void
    {
        $table = 'stranger \'table';
        $tableComment = 'A comment for stranger \'table.';
        $this->runAddCommentOnTable($tableComment, $table);
        $this->runDropCommentFromTable($table);

        $result = $this->getCommmentsFromTable($table);
        $this->assertEquals([], $result);

        $column = 'stranger \'field';
        $columnComment = 'A comment for stranger \'field in stranger \'table.';
        $this->runAddCommentOnColumn($columnComment, $table, $column);
        $this->runDropCommentFromColumn($table, $column);

        $result = $this->getCommentsFromColumn($table, $column);
        $this->assertEquals([], $result);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::createDropIndexesProvider
     *
     * @param string $sql
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::deleteProvider()
     *
     * @param string $table
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     */
    public function testDelete(string $table, $condition, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->delete($table, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::insertProvider()
     *
     * @param string $table
     * @param array|ColumnSchema $columns
     * @param array $params
     * @param string $expectedSQL
     * @param array $expectedParams
     */
    public function testInsert(string $table, $columns, array $params, string $expectedSQL, array $expectedParams): void
    {
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->insert($table, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }

    public function testLimit(): void
    {
        $db = $this->getConnection();
        $expectedQuerySql = 'SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY';
        $expectedQueryParams = [];
        $query = new Query($db);
        $query->select('id')->from('example')->limit(10);
        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testOffset(): void
    {
        $db = $this->getConnection();
        $expectedQuerySql = 'SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 10 ROWS';
        $expectedQueryParams = [];
        $query = new Query($db);
        $query->select('id')->from('example')->offset(10);
        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testOffsetLimit(): void
    {
        $db = $this->getConnection();
        $expectedQuerySql = 'SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY';
        $expectedQueryParams = [];
        $query = new Query($db);
        $query->select('id')->from('example')->limit(10)->offset(5);
        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function buildFromDataProvider(): array
    {
        $data = $this->buildFromDataProviderTrait();

        $data[] = ['[test]', '[[test]]'];
        $data[] = ['[test] [t1]', '[[test]] [[t1]]'];
        $data[] = ['[table.name]', '[[table.name]]'];
        $data[] = ['[table.name.with.dots]', '[[table.name.with.dots]]'];
        $data[] = ['[table name]', '[[table name]]'];
        $data[] = ['[table name with spaces]', '[[table name with spaces]]'];

        return $data;
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::updateProvider()
     *
     * @param string $table
     * @param array $columns
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     */
    public function testUpdate(
        string $table,
        array $columns,
        $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->update($table, $columns, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::upsertProvider
     *
     * @param string $table
     * @param array|ColumnSchema|Query $insertColumns
     * @param array|bool|null $updateColumns
     * @param string|string[] $expectedSQL
     * @param array|string $expectedParams
     *
     * @throws Exception|NotSupportedException
     */
    public function testUpsert(
        string $table,
        array|ColumnSchema|Query $insertColumns,
        array|bool|null $updateColumns,
        array|string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];
        $db = $this->getConnection();
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);

        if (is_string($expectedSQL)) {
            $this->assertSame($expectedSQL, $actualSQL);
        } else {
            $this->assertContains($actualSQL, $expectedSQL);
        }

        if (ArrayHelper::isAssociative($expectedParams)) {
            $this->assertSame($expectedParams, $actualParams);
        } else {
            $this->assertIsOneOf($actualParams, $expectedParams);
        }
    }

    public function testUpsertVarbinary()
    {
        $db = $this->getConnection();
        $testData = json_encode(['test' => 'string', 'test2' => 'integer']);
        $params = [];

        $sql = $db->getQueryBuilder()->upsert(
            'T_upsert_varbinary',
            ['id' => 1, 'blob_col' => $testData],
            ['blob_col' => $testData],
            $params
        );

        $result = $db->createCommand($sql, $params)->execute();
        $this->assertEquals(1, $result);

        $query = (new Query($db))->select(['blob_col as blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();
        $this->assertEquals($testData, $resultData['blob_col']);
    }

    protected function getCommentsFromColumn(string $table, string $column): array
    {
        $db = $this->getConnection();
        $sql = "SELECT *
            FROM fn_listextendedproperty (
                N'MS_description',
                'SCHEMA', N'dbo',
                'TABLE', N" . $db->getQuoter()->quoteValue($table) . ",
                'COLUMN', N" . $db->getQuoter()->quoteValue($column) . '
        )';
        return $db->createCommand($sql)->queryAll();
    }

    protected function getCommmentsFromTable(string $table): array
    {
        $db = $this->getConnection();
        $sql = "SELECT *
            FROM fn_listextendedproperty (
                N'MS_description',
                'SCHEMA', N'dbo',
                'TABLE', N" . $db->getQuoter()->quoteValue($table) . ',
                DEFAULT, DEFAULT
        )';
        return $db->createCommand($sql)->queryAll();
    }

    protected function runAddCommentOnColumn(string $comment, string $table, string $column): int
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnColumn($table, $column, $comment);
        return $db->createCommand($sql)->execute();
    }

    protected function runAddCommentOnTable(string $comment, string $table): int
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnTable($table, $comment);
        return $db->createCommand($sql)->execute();
    }

    protected function runDropCommentFromColumn(string $table, string $column): int
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();
        $sql = $qb->dropCommentFromColumn($table, $column);
        return $db->createCommand($sql)->execute();
    }

    protected function runDropCommentFromTable(string $table): int
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();
        $sql = $qb->dropCommentFromTable($table);
        return $db->createCommand($sql)->execute();
    }
}
