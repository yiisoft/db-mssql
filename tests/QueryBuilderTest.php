<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use JsonException;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mssql\Column;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\SchemaInterface;
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
                        'COLUMN', N'id' ";

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
                         DEFAULT, DEFAULT ";

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
            $qb->addDefaultValue('T_constraints_1', 'CN_pk', 'C_default', 1),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildCondition
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
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
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::buildLikeCondition
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
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
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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
                        'COLUMN', N'id' ";

        $this->assertStringContainsString($sql, $qb->dropCommentFromColumn('customer', 'id'));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropCommentFromColumnException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->dropCommentFromColumn('noExist', 'id');
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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
                         DEFAULT, DEFAULT ";

        $this->assertStringContainsString($sql, $qb->dropCommentFromTable('customer'));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
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
            $qb->dropDefaultValue('T_constraints_1', 'CN_pk'),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE [test_uq] DROP CONSTRAINT [test_uq_constraint]
            SQL,
            $qb->dropUnique('test_uq', 'test_uq_constraint'),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::insert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
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
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsertWithReturningPks($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::selectExist
     */
    public function testSelectExists(string $sql, string $expected): void
    {
        parent::testSelectExists($sql, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::upsert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider::upsert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpsertExecute(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testUpsertExecute($table, $insertColumns, $updateColumns);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpsertVarbinary(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $testData = json_encode(['test' => 'string', 'test2' => 'integer'], JSON_THROW_ON_ERROR);
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAlterColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] varchar(255)
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END";
        $sql = $qb->alterColumn('foo1', 'bar', 'varchar(255)');
        $this->assertEquals($expected, $sql);

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) NOT NULL
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 255))->notNull()
        );
        $this->assertEquals($expected, $sql);

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255)
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [CK_foo1_bar] CHECK (LEN(bar) > 5)";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 255))->check('LEN(bar) > 5')
        );
        $this->assertEquals($expected, $sql);

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255)
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT '' FOR [bar]";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 255))->defaultValue('')
        );
        $this->assertEquals($expected, $sql);

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255)
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT 'AbCdE' FOR [bar]";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 255))->defaultValue('AbCdE')
        );
        $this->assertEquals($expected, $sql);

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] datetime
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT CURRENT_TIMESTAMP FOR [bar]";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_TIMESTAMP))->defaultExpression('CURRENT_TIMESTAMP')
        );
        $this->assertEquals($expected, $sql);

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(30)
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [UQ_foo1_bar] UNIQUE ([bar])";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 30))->unique()
        );
        $this->assertEquals($expected, $sql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAlterColumnOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn('foo1', 'bar', 'varchar(255)');
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);

        $this->assertSame('varchar(255)', $schema?->getColumn('bar')->getDbType());
        $this->assertSame(true, $schema?->getColumn('bar')->isAllowNull());

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 128))->notNull()
        );
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);

        $this->assertSame('nvarchar(128)', $schema?->getColumn('bar')->getDbType());
        $this->assertSame(false, $schema?->getColumn('bar')->isAllowNull());

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_TIMESTAMP))->defaultExpression('CURRENT_TIMESTAMP')
        );
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);
        $this->assertSame(SchemaInterface::TYPE_DATETIME, $schema?->getColumn('bar')->getDbType());
        $this->assertSame('getdate()', $schema?->getColumn('bar')->getDefaultValue());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAlterColumnWithCheckConstraintOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 128))->null()->check('LEN(bar) > 5')
        );
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);
        $this->assertEquals('nvarchar(128)', $schema?->getColumn('bar')->getDbType());
        $this->assertEquals(true, $schema?->getColumn('bar')->isAllowNull());

        $sql = "INSERT INTO [foo1]([bar]) values('abcdef')";
        $this->assertEquals(1, $db->createCommand($sql)->execute());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAlterColumnWithCheckConstraintOnDbWithException(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 64))->check('LEN(bar) > 5')
        );
        $db->createCommand($sql)->execute();

        $sql = "INSERT INTO [foo1]([bar]) values('abcde')";
        $this->expectException(IntegrityException::class);
        $this->assertEquals(1, $db->createCommand($sql)->execute());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAlterColumnWithUniqueConstraintOnDbWithException(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 64))->unique()
        );
        $db->createCommand($sql)->execute();

        $sql = "INSERT INTO [foo1]([bar]) values('abcdef')";
        $this->assertEquals(1, $db->createCommand($sql)->execute());

        $this->expectException(IntegrityException::class);
        $this->assertEquals(1, $db->createCommand($sql)->execute());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $expected = "DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
        )
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] DROP COLUMN [bar]";
        $sql = $qb->dropColumn('foo1', 'bar');
        $this->assertEquals($expected, $sql);

        $expected = "DECLARE @tableName VARCHAR(MAX) = '[customer]'
DECLARE @columnName VARCHAR(MAX) = 'id'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
        )
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [customer] DROP COLUMN [id]";
        $sql = $qb->dropColumn('customer', 'id');
        $this->assertEquals($expected, $sql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropColumnOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_STRING, 64))
                ->defaultValue('')
                ->check('LEN(bar) < 5')
                ->unique()
        );
        $db->createCommand($sql)->execute();

        $sql = $db->getQueryBuilder()->dropColumn('foo1', 'bar');
        $this->assertEquals(0, $db->createCommand($sql)->execute());

        $schema = $db->getTableSchema('[foo1]', true);
        $this->assertEquals(null, $schema?->getColumn('bar'));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAlterColumnWithNull(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] int NULL
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT NULL FOR [bar]";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_INTEGER))->null()->defaultValue(null)
        );
        $this->assertEquals($expected, $sql);

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] int NULL
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT NULL FOR [bar]";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_INTEGER))->defaultValue(null)
        );
        $this->assertEquals($expected, $sql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAlterColumnWithExpression(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();

        $expected = "ALTER TABLE [foo1] ALTER COLUMN [bar] int NULL
DECLARE @tableName VARCHAR(MAX) = '[foo1]'
DECLARE @columnName VARCHAR(MAX) = 'bar'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
         WHERE so.[type]='D')
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END
ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT CAST(GETDATE() AS INT) FOR [bar]";
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            (new Column(SchemaInterface::TYPE_INTEGER))
                ->null()
                ->defaultValue(new Expression('CAST(GETDATE() AS INT)'))
        );

        $this->assertEquals($expected, $sql);
    }
}
