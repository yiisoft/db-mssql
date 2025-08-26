<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\IntegrityException;
use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

use Yiisoft\Db\Tests\Support\Assert;
use function json_encode;

/**
 * @group mssql
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use TestTrait;

    public function getBuildColumnDefinitionProvider(): array
    {
        return QueryBuilderProvider::buildColumnDefinition();
    }

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

    public function testAddCommentOnColumnException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->addCommentOnColumn('noExist', 'id', 'Primary key.');
    }

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

    public function testAddCommentOnTableException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->addCommentOnTable('noExist', 'Customer table.');
    }

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

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildCondition')]
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

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildLikeCondition')]
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

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
                        'COLUMN', N'id' ";

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
                         DEFAULT, DEFAULT ";

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

    #[DataProviderExternal(QueryBuilderProvider::class, 'insert')]
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $rows,
        array $columns,
        string $expected,
        array $expectedParams = [],
    ): void {
        parent::testBatchInsert($table, $rows, $columns, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertReturningPks')]
    public function testInsertReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsertReturningPks($table, $columns, $params, $expectedSQL, $expectedParams);
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

    public function testSelectExists(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $sql = 'SELECT 1 FROM [customer] WHERE [id] = 1';
        // Alias is not required in MSSQL, but it is added for consistency with other DBMS.
        $expected = 'SELECT CASE WHEN EXISTS(SELECT 1 FROM [customer] WHERE [id] = 1) THEN 1 ELSE 0 END AS [0]';

        $this->assertSame($expected, $qb->selectExists($sql));
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        array $params,
        string $expectedSql,
        array $expectedParams = [],
    ): void {
        parent::testUpdate($table, $columns, $condition, $params, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSql,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSql, $expectedParams);
    }

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

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertReturning')]
    public function testUpsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array|null $returnColumns,
        string $expectedSql,
        array $expectedParams
    ): void {
        parent::testUpsertReturning($table, $insertColumns, $updateColumns, $returnColumns, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'alterColumn')]
    public function testAlterColumn(string|ColumnInterface $type, string $expected): void
    {
        parent::testAlterColumn($type, $expected);
    }

    public function testAlterColumnOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn('foo1', 'bar', 'varchar(255)');
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);

        $this->assertSame('varchar', $schema?->getColumn('bar')->getDbType());
        $this->assertSame(255, $schema?->getColumn('bar')->getSize());
        $this->assertFalse($schema?->getColumn('bar')->isNotNull());

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::string(128)->notNull()
        );
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);

        $this->assertSame('nvarchar', $schema?->getColumn('bar')->getDbType());
        $this->assertSame(128, $schema?->getColumn('bar')->getSize());
        $this->assertTrue($schema?->getColumn('bar')->isNotNull());

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::timestamp()->defaultValue(new Expression('CURRENT_TIMESTAMP'))
        );
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);
        $this->assertSame('datetime2', $schema?->getColumn('bar')->getDbType());
        $this->assertEquals(new Expression('getdate()'), $schema?->getColumn('bar')->getDefaultValue());
    }

    public function testAlterColumnWithCheckConstraintOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::string(128)->null()->check('LEN(bar) > 5')
        );
        $db->createCommand($sql)->execute();
        $schema = $db->getTableSchema('[foo1]', true);
        $this->assertSame('nvarchar', $schema?->getColumn('bar')->getDbType());
        $this->assertSame(128, $schema?->getColumn('bar')->getSize());
        $this->assertFalse($schema?->getColumn('bar')->isNotNull());

        $sql = "INSERT INTO [foo1]([bar]) values('abcdef')";
        $this->assertEquals(1, $db->createCommand($sql)->execute());
    }

    public function testAlterColumnWithCheckConstraintOnDbWithException(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::string(64)->check('LEN(bar) > 5')
        );
        $db->createCommand($sql)->execute();

        $sql = "INSERT INTO [foo1]([bar]) values('abcde')";
        $this->expectException(IntegrityException::class);
        $this->assertEquals(1, $db->createCommand($sql)->execute());
    }

    public function testAlterColumnWithUniqueConstraintOnDbWithException(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::string(64)->unique()
        );
        $db->createCommand($sql)->execute();

        $sql = "INSERT INTO [foo1]([bar]) values('abcdef')";
        $this->assertEquals(1, $db->createCommand($sql)->execute());

        $this->expectException(IntegrityException::class);
        $this->assertEquals(1, $db->createCommand($sql)->execute());
    }

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

    public function testDropColumnOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->getQueryBuilder()->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::string(64)
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

    public function testAlterColumnWithNull(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();

        $expected = <<<SQL
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
            ALTER TABLE [foo1] ALTER COLUMN [bar] int NULL
            ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT NULL FOR [bar]
            SQL;
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::integer()->null()->defaultValue(null)
        );
        $this->assertEquals($expected, $sql);

        $expected = <<<SQL
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
            ALTER TABLE [foo1] ALTER COLUMN [bar] int
            ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT NULL FOR [bar]
            SQL;
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::integer()->defaultValue(null)
        );
        $this->assertEquals($expected, $sql);
    }

    public function testAlterColumnWithExpression(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();

        $expected = <<<SQL
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
            ALTER TABLE [foo1] ALTER COLUMN [bar] int NULL
            ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT CAST(GETDATE() AS INT) FOR [bar]
            SQL;
        $sql = $qb->alterColumn(
            'foo1',
            'bar',
            ColumnBuilder::integer()
                ->null()
                ->defaultValue(new Expression('CAST(GETDATE() AS INT)'))
        );

        $this->assertEquals($expected, $sql);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'selectScalar')]
    public function testSelectScalar(array|bool|float|int|string $columns, string $expected): void
    {
        parent::testSelectScalar($columns, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildColumnDefinition')]
    public function testBuildColumnDefinition(string $expected, ColumnInterface|string $column): void
    {
        parent::testBuildColumnDefinition($expected, $column);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildValue')]
    public function testBuildValue(mixed $value, string $expected, array $expectedParams = []): void
    {
        parent::testBuildValue($value, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'prepareParam')]
    public function testPrepareParam(string $expected, mixed $value, int $type): void
    {
        parent::testPrepareParam($expected, $value, $type);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'prepareValue')]
    public function testPrepareValue(string $expected, mixed $value): void
    {
        parent::testPrepareValue($expected, $value);
    }

    #[DataProvider('dataDropTable')]
    public function testDropTable(string $expected, ?bool $ifExists, ?bool $cascade): void
    {
        if ($cascade) {
            $qb = $this->getConnection()->getQueryBuilder();

            $this->expectException(NotSupportedException::class);
            $this->expectExceptionMessage('MSSQL doesn\'t support cascade drop table.');

            $ifExists === null
                ? $qb->dropTable('customer', cascade: true)
                : $qb->dropTable('customer', ifExists: $ifExists, cascade: true);

            return;
        }

        parent::testDropTable($expected, $ifExists, $cascade);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'caseExpressionBuilder')]
    public function testCaseExpressionBuilder(
        CaseExpression $case,
        string $expectedSql,
        array $expectedParams,
        string|int $expectedResult,
    ): void {
        parent::testCaseExpressionBuilder($case, $expectedSql, $expectedParams, $expectedResult);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'delete')]
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        parent::testDelete($table, $condition, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'lengthBuilder')]
    public function testLengthBuilder(
        string|ExpressionInterface $operand,
        string $expectedSql,
        int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testLengthBuilder($operand, $expectedSql, $expectedResult, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'multiOperandFunctionBuilder')]
    public function testMultiOperandFunctionBuilder(
        string $class,
        array $operands,
        string $expectedSql,
        array|string|int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testMultiOperandFunctionBuilder($class, $operands, $expectedSql, $expectedResult, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'multiOperandFunctionClasses')]
    public function testMultiOperandFunctionBuilderWithoutOperands(string $class): void
    {
        parent::testMultiOperandFunctionBuilderWithoutOperands($class);
    }

    public function testArrayMergeWithOrdering(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $stringParam = new Param('[4,3,5]', DataType::STRING);
        $arrayMerge = (new ArrayMerge(
            "'[2,1,3]'",
            [6, 5, 7],
            $stringParam,
            self::getDb()->select(new ArrayExpression([10, 9])),
        ))->ordered();
        $params = [];

        $this->assertSame(
            "(SELECT '[' + STRING_AGG('\"' + STRING_ESCAPE(value, 'json') + '\"', ',')"
            . " WITHIN GROUP (ORDER BY value) + ']' AS value FROM ("
            . "SELECT value FROM OPENJSON('[2,1,3]')"
            . ' UNION SELECT value FROM OPENJSON(:qp0)'
            . ' UNION SELECT value FROM OPENJSON(:qp1)'
            . ' UNION SELECT value FROM OPENJSON((SELECT :qp2))'
            . ') AS t)',
            $qb->buildExpression($arrayMerge, $params)
        );
        Assert::arraysEquals(
            [
                ':qp0' => new Param('[6,5,7]', DataType::STRING),
                ':qp1' => $stringParam,
                ':qp2' => new Param('[10,9]', DataType::STRING),
            ],
            $params,
        );

        $result = $db->select($arrayMerge)->scalar();

        $this->assertEquals('["1","10","2","3","4","5","6","7","9"]', $result);
    }
}
