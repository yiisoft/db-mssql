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
        $sql = $qb->addCommentOnColumn('customer', 'id', 'Primary key.');

        $this->assertStringContainsString('IF NOT EXISTS (', $sql);
    }

    /**
     * @throws Exception
     */
    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnTable('customer', 'Customer table.');

        $this->assertStringContainsString('IF NOT EXISTS (', $sql);
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addDefaultValue('name', 'table', 'column', 'value');

        $this->assertSame(
            <<<SQL
            ALTER TABLE [table] ADD CONSTRAINT [name] DEFAULT 'value' FOR [column]
            SQL,
            $sql,
        );
    }

    public function testAlterColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $schema = $db->getSchema();
        $sql = $qb->alterColumn('table', 'column', (string) $schema::TYPE_STRING);

        $this->assertSame(
            <<<SQL
            ALTER TABLE [table] ALTER COLUMN [column] nvarchar(255)
            SQL,
            $sql,
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
        $sql = $qb->renameColumn('alpha', 'string_identifier', 'string_identifier_test');

        $this->assertSame(
            <<<SQL
            sp_rename '[alpha].[string_identifier]', [string_identifier_test], 'COLUMN'
            SQL,
            $sql,
        );
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->renameTable('alpha', 'alpha-test');

        $this->assertSame(
            <<<SQL
            sp_rename [alpha], [alpha-test]
            SQL,
            $sql,
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
}
