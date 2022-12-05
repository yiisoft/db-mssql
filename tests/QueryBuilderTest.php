<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

use function json_encode;

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
    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = "
            IF NOT EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', N'dbo',
                        'TABLE', N'customer',
        ";

        $this->assertStringContainsString($sql, $qb->addCommentOnColumn('customer', 'id', 'Primary key.'));
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

        $qb->addCommentOnColumn('noExist', 'id', 'Primary key.');
    }

    /**
     * @throws Exception
     */
    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = "
            IF NOT EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', N'dbo',
                        'TABLE', N'customer',
        ";

        $this->assertStringContainsString($sql, $qb->addCommentOnTable('customer', 'Customer table.'));
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

        $qb->addCommentOnTable('noExist', 'Customer table.');
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [T_constraints_1] ADD CONSTRAINT [CN_pk] DEFAULT 1 FOR [C_default]
            SQL,
            $qb->addDefaultValue('CN_pk', 'T_constraints_1', 'C_default', 1),
        );
    }

    public function testAlterColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $schema = $db->getSchema();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [customer] ALTER COLUMN [email] nvarchar(255)
            SQL,
            $qb->alterColumn('customer', 'email', (string) $schema::TYPE_STRING),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildCondition()
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    public function testBuildFrom(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user');
        $params = [];

        $this->assertSame(
            <<<SQL
            FROM [admin_user]
            SQL,
            $qb->buildFrom($query->getFrom(), $params),
        );

        $query = (new Query($db))->from('[admin_user]');

        $this->assertSame(
            <<<SQL
            FROM [admin_user]
            SQL,
            $qb->buildFrom($query->getFrom(), $params),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildLikeCondition()
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
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->limit(10);

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
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->offset(10);

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
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testBuildOrderByAndLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))
            ->from('admin_user')
            ->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC])
            ->limit(10)
            ->offset(5);

        $this->assertSame(
            <<<SQL
            SELECT * FROM [admin_user] ORDER BY [id], [name] DESC OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY
            SQL,
            $qb->buildOrderByAndLimit(
                <<<SQL
                SELECT * FROM [admin_user]
                SQL,
                $query->getOrderBy(),
                $query->getLimit(),
                $query->getOffset(),
            ),
        );
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [dbo].[customer] CHECK CONSTRAINT ALL;
            SQL . ' ',
            $qb->checkIntegrity('dbo', 'customer'),
        );
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            CREATE TABLE [test] (
            \t[id] int IDENTITY PRIMARY KEY,
            \t[name] nvarchar(255) NOT NULL,
            \t[email] nvarchar(255) NOT NULL,
            \t[status] int NOT NULL,
            \t[created_at] datetime NOT NULL
            )
            SQL,
            $qb->createTable(
                'test',
                [
                    'id' => 'pk',
                    'name' => 'string(255) NOT NULL',
                    'email' => 'string(255) NOT NULL',
                    'status' => 'integer NOT NULL',
                    'created_at' => 'datetime NOT NULL',
                ],
            ),
        );
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $sql = "
            IF EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', N'dbo',
                        'TABLE', N'customer',
        ";

        $this->assertStringContainsString($sql, $qb->dropCommentFromColumn('customer', 'id'));
    }

    public function testDropCommentFromColumnException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->dropCommentFromColumn('noExist', 'id');
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $sql = "
            IF EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', N'dbo',
                        'TABLE', N'customer',
        ";

        $this->assertStringContainsString($sql, $qb->dropCommentFromTable('customer'));
    }

    public function testDropCommentFromTableException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->dropCommentFromTable('noExist');
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [T_constraints_1] DROP CONSTRAINT [CN_pk]
            SQL,
            $qb->dropDefaultValue('CN_pk', 'T_constraints_1'),
        );
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

    public function testRenameColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            sp_rename '[alpha].[string_identifier]', [string_identifier_test], 'COLUMN'
            SQL,
            $qb->renameColumn('alpha', 'string_identifier', 'string_identifier_test'),
        );
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            sp_rename [alpha], [alpha-test]
            SQL,
            $qb->renameTable('alpha', 'alpha-test'),
        );
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            DBCC CHECKIDENT ('[item]', RESEED, 0) WITH NO_INFOMSGS;DBCC CHECKIDENT ('[item]', RESEED)
            SQL,
            $qb->resetSequence('item'),
        );

        $this->assertSame(
            <<<SQL
            DBCC CHECKIDENT ('[item]', RESEED, 3)
            SQL,
            $qb->resetSequence('item', 3),
        );
    }

    public function testResetSequenceException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is not sequence associated with table 'noExist'.");

        $qb->resetSequence('noExist');
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::selectExist()
     */
    public function testSelectExists(string $sql, string $expected): void
    {
        parent::testSelectExists($sql, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::upsert()
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string|array $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    public function testUpsertVarbinary()
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $testData = json_encode(['test' => 'string', 'test2' => 'integer']);
        $params = [];
        $result = $db->createCommand(
            $qb->upsert(
                'T_upsert_varbinary',
                ['id' => 1, 'blob_col' => $testData],
                ['blob_col' => $testData],
                $params,
            ),
            $params,
        )->execute();

        $this->assertSame(1, $result);

        $query = (new Query($db))->select(['blob_col as blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();

        $this->assertIsArray($resultData);
        $this->assertSame($testData, $resultData['blob_col']);
    }
}
