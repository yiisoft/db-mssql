<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\Tests\Support\TraversableObject;

use function array_replace;
use function preg_replace;
use function rtrim;
use function str_replace;
use function version_compare;

final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'sqlsrv';

    /**
     * @var string ` ESCAPE 'char'` part of a LIKE condition SQL.
     */
    protected static string $likeEscapeCharSql = '';

    /**
     * @var array map of values to their replacements in LIKE query params.
     */
    protected static array $likeParameterReplacements = [
        '\%' => '[%]',
        '\_' => '[_]',
        '[' => '[[]',
        ']' => '[]]',
        '\\\\' => '[\\]',
    ];

    public static function alterColumn(): array
    {
        return [
            [
                'varchar(255)',
                <<<SQL
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
                ALTER TABLE [foo1] ALTER COLUMN [bar] varchar(255)
                SQL,
            ], [
                ColumnBuilder::string()->notNull(),
                <<<SQL
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
                ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) NOT NULL
                SQL,
            ], [
                ColumnBuilder::string()->check('LEN(bar) > 5'),
                <<<SQL
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
                ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255)
                ALTER TABLE [foo1] ADD CONSTRAINT [CK_foo1_bar] CHECK (LEN(bar) > 5)
                SQL,
            ], [
                ColumnBuilder::string()->defaultValue(''),
                <<<SQL
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
                ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255)
                ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT '' FOR [bar]
                SQL,
            ], [
                ColumnBuilder::string()->defaultValue('AbCdE'),
                <<<SQL
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
                ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255)
                ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT 'AbCdE' FOR [bar]
                SQL,
            ], [
                ColumnBuilder::timestamp()->defaultValue(new Expression('CURRENT_TIMESTAMP')),
                <<<SQL
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
                ALTER TABLE [foo1] ALTER COLUMN [bar] datetime2(0)
                ALTER TABLE [foo1] ADD CONSTRAINT [DF_foo1_bar] DEFAULT CURRENT_TIMESTAMP FOR [bar]
                SQL,
            ], [
                ColumnBuilder::string(30)->unique(),
                <<<SQL
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
                ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(30)
                ALTER TABLE [foo1] ADD CONSTRAINT [UQ_foo1_bar] UNIQUE ([bar])
                SQL,
            ],
        ];
    }

    public static function buildCondition(): array
    {
        $buildCondition = parent::buildCondition();

        $buildCondition['inCondition-custom-1'] = [new In(['id', 'name'], []), '()', []];
        $buildCondition['inCondition-custom-3'] = [
            new In(['id', 'name'], [['id' => 1]]),
            '(([id] = :qp0 AND [name] IS NULL))',
            [':qp0' => 1],
        ];
        $buildCondition['inCondition-custom-4'] = [
            new In(['id', 'name'], [['name' => 'oy']]),
            '(([id] IS NULL AND [name] = :qp0))',
            [':qp0' => 'oy'],
        ];

        $buildCondition['inCondition-custom-5'] = [
            new In(['id', 'name'], [['id' => 1, 'name' => 'oy']]),
            '(([id] = :qp0 AND [name] = :qp1))',
            [':qp0' => 1, ':qp1' => 'oy'],
        ];
        $buildCondition['composite in'] = [
            ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
            '(([id] = :qp0 AND [name] = :qp1))',
            [':qp0' => 1, ':qp1' => 'oy'],
        ];
        $buildCondition['composite in with Expression'] = [
            ['in',
                [new Expression('id'), new Expression('name')],
                [['id' => 1, 'name' => 'oy']],
            ],
            '((id = :qp0 AND name = :qp1))',
            [':qp0' => 1, ':qp1' => 'oy'],
        ];
        $buildCondition['composite in using array objects'] = [
            [
                'in',
                new TraversableObject(['id', 'name']),
                new TraversableObject(
                    [
                        ['id' => 1, 'name' => 'oy'],
                        ['id' => 2, 'name' => 'yo'],
                    ]
                ),
            ],
            '(([id] = :qp0 AND [name] = :qp1) OR ([id] = :qp2 AND [name] = :qp3))',
            [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
        ];

        $buildCondition['and-subquery']['1'] = '([[expired]] = 0) AND ((SELECT count(*) > 1 FROM [[queue]]))';

        unset($buildCondition['inCondition-custom-2'], $buildCondition['inCondition-custom-6']);

        return $buildCondition;
    }

    public static function selectScalar(): array
    {
        $data = parent::selectScalar();

        $data['true'][1] = 'SELECT 1';
        $data['false'][1] = 'SELECT 0';
        $data['array'][1] = 'SELECT 1, 1, 12.34';
        $data['string keys'][1] = 'SELECT 1 AS [a], 1 AS [b], 12.34';

        return $data;
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['regular-values'][3] = <<<SQL
        INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) VALUES (:qp0, :qp1, :qp2, 0, NULL)
        SQL;

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO [customer] DEFAULT VALUES
        SQL;

        $insert['carry passed params'][3] = <<<SQL
        INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id], [col]) VALUES (:qp1, :qp2, :qp3, 0, NULL, CONCAT(:phFoo, :phBar))
        SQL;

        $insert['carry passed params (query)'][3] = <<<SQL
        INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) SELECT [email], [name], [address], [is_active], [related_id] FROM [customer] WHERE ([email] = :qp1) AND ([name] = :qp2) AND ([address] = :qp3) AND ([is_active] = 0) AND ([related_id] IS NULL) AND ([col] = CONCAT(:phFoo, :phBar))
        SQL;

        return $insert;
    }

    public static function batchInsert(): array
    {
        $values = parent::batchInsert();

        foreach ($values as &$value) {
            $value['expected'] = preg_replace(['/\bTRUE\b/i', '/\bFALSE\b/i'], ['1', '0'], $value['expected']);
        }

        return $values;
    }

    public static function insertReturningPks(): array
    {
        return [
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int);INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id] INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, 0, NULL);SELECT * FROM @temporary_inserted;
                SQL,
                [
                    ':qp0' => new Param('test@example.com', DataType::STRING),
                    ':qp1' => new Param('silverfire', DataType::STRING),
                    ':qp2' => new Param('Kyiv {{city}}, Ukraine', DataType::STRING),
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                ['{{%type}}.[[related_id]]' => null, '[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                INSERT INTO {{%type}} ([related_id], [time]) VALUES (NULL, now())
                SQL,
                [],
            ],
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int);INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id], [col]) OUTPUT INSERTED.[id] INTO @temporary_inserted VALUES (:qp1, :qp2, :qp3, 0, NULL, CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => new Param('test@example.com', DataType::STRING),
                    ':qp2' => new Param('sergeymakinen', DataType::STRING),
                    ':qp3' => new Param('{{city}}', DataType::STRING),
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                (new Query(self::getDb()))
                    ->select(['email', 'name', 'address', 'is_active', 'related_id'])
                    ->from('customer')
                    ->where(
                        [
                            'email' => 'test@example.com',
                            'name' => 'sergeymakinen',
                            'address' => '{{city}}',
                            'is_active' => false,
                            'related_id' => null,
                            'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                        ],
                    ),
                [':phBar' => 'bar'],
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int);INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id] INTO @temporary_inserted SELECT [email], [name], [address], [is_active], [related_id] FROM [customer] WHERE ([email] = :qp1) AND ([name] = :qp2) AND ([address] = :qp3) AND ([is_active] = 0) AND ([related_id] IS NULL) AND ([col] = CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => new Param('test@example.com', DataType::STRING),
                    ':qp2' => new Param('sergeymakinen', DataType::STRING),
                    ':qp3' => new Param('{{city}}', DataType::STRING),
                    ':phFoo' => 'foo',
                ],
            ],
            [
                '{{%order_item}}',
                ['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 1.0],
                [],
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([order_id] int, [item_id] int);INSERT INTO {{%order_item}} ([order_id], [item_id], [quantity], [subtotal]) OUTPUT INSERTED.[order_id],INSERTED.[item_id] INTO @temporary_inserted VALUES (1, 1, 1, 1);SELECT * FROM @temporary_inserted;
                SQL,
                [],
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, 1, NULL)) AS EXCLUDED ' .
                    '([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=EXCLUDED.[address], [status]=EXCLUDED.[status], [profile_id]=EXCLUDED.[profile_id] ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES (EXCLUDED.[email], ' .
                    'EXCLUDED.[address], EXCLUDED.[status], EXCLUDED.[profile_id]);',
            ],

            'regular values with unique at not the first position' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, 1, NULL)) AS EXCLUDED ' .
                    '([address], [email], [status], [profile_id]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=EXCLUDED.[address], [status]=EXCLUDED.[status], [profile_id]=EXCLUDED.[profile_id] ' .
                    'WHEN NOT MATCHED THEN INSERT ([address], [email], [status], [profile_id]) VALUES (' .
                    'EXCLUDED.[address], EXCLUDED.[email], EXCLUDED.[status], EXCLUDED.[profile_id]);',
            ],

            'regular values with update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, 1, NULL)) AS EXCLUDED ' .
                    '([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=:qp2, [status]=2, [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) ' .
                    'VALUES (EXCLUDED.[email], EXCLUDED.[address], EXCLUDED.[status], EXCLUDED.[profile_id]);',
            ],

            'regular values without update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, 1, NULL)) AS EXCLUDED ' .
                    '([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) ' .
                    'VALUES (EXCLUDED.[email], EXCLUDED.[address], EXCLUDED.[status], EXCLUDED.[profile_id]);',
            ],

            'query' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING ' .
                    '(SELECT [email], 2 AS [status] FROM [customer] WHERE [name] = :qp0 ' .
                    'ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS EXCLUDED ' .
                    '([email], [status]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [status]=EXCLUDED.[status] ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES (EXCLUDED.[email], EXCLUDED.[status]);',
            ],

            'query with update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] ' .
                    'WHERE [name] = :qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS EXCLUDED ' .
                    '([email], [status]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=:qp1, [status]=2, [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES (EXCLUDED.[email], EXCLUDED.[status]);',
            ],

            'query without update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] ' .
                    'WHERE [name] = :qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS EXCLUDED ' .
                    '([email], [status]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES (EXCLUDED.[email], EXCLUDED.[status]);',
            ],

            'values and expressions' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)')],
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS EXCLUDED ' .
                    '([email], [ts]) ON ({{%T_upsert}}.[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [ts]=EXCLUDED.[ts] ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES (EXCLUDED.[email], EXCLUDED.[ts]);',
            ],

            'values and expressions with update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)')],
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS EXCLUDED ' .
                    '([email], [ts]) ON ({{%T_upsert}}.[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES (EXCLUDED.[email], EXCLUDED.[ts]);',
            ],

            'values and expressions without update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)')],
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS EXCLUDED ' .
                    '([email], [ts]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES (EXCLUDED.[email], EXCLUDED.[ts]);',
            ],

            'query, values and expressions with update part' => [
                1 => (new Query(self::getDb()))
                        ->select(
                            [
                                'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                                '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                            ],
                        ),
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], CONVERT(bigint, CURRENT_TIMESTAMP) AS [[ts]]) ' .
                    'AS EXCLUDED ([email], [ts]) ON ({{%T_upsert}}.[email]=EXCLUDED.[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [ts]=0, [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES (EXCLUDED.[email], EXCLUDED.[ts]);',
            ],

            'query, values and expressions without update part' => [
                1 => (new Query(self::getDb()))
                        ->select(
                            [
                                'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                                '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                            ],
                        ),
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], CONVERT(bigint, CURRENT_TIMESTAMP) AS [[ts]]) ' .
                    'AS EXCLUDED ([email], [ts]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES (EXCLUDED.[email], EXCLUDED.[ts]);',
            ],
            'no columns to update' => [
                3 => 'MERGE [T_upsert_1] WITH (HOLDLOCK) USING (VALUES (1)) AS EXCLUDED ' .
                    '([a]) ON ([T_upsert_1].[a]=EXCLUDED.[a]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([a]) VALUES (EXCLUDED.[a]);',
            ],
            'no columns to update with unique' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0)) AS EXCLUDED ' .
                    '([email]) ON ([T_upsert].[email]=EXCLUDED.[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email]) VALUES (EXCLUDED.[email]);',
            ],
            'no unique columns in table - simple insert' => [
                3 => 'INSERT INTO {{%animal}} ([type]) VALUES (:qp0);',
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }

    public static function upsertReturning(): array
    {
        $upsert = self::upsert();

        foreach ($upsert as &$data) {
            array_splice($data, 3, 0, [['id']]);
            $data[4] = 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int);'
                . rtrim($data[4], ';')
                . ' OUTPUT INSERTED.[id] INTO @temporary_inserted;SELECT * FROM @temporary_inserted;';
        }

        $upsert['regular values without update part'][4] = 'SET NOCOUNT ON;'
            . 'DECLARE @temporary_inserted TABLE ([id] int);DECLARE @temp int;'
            . 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, 1, NULL)) AS EXCLUDED'
            . ' ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=EXCLUDED.[email])'
            . ' WHEN MATCHED THEN UPDATE SET @temp=1'
            . ' WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id])'
            . ' VALUES (EXCLUDED.[email], EXCLUDED.[address], EXCLUDED.[status], EXCLUDED.[profile_id])'
            . ' OUTPUT INSERTED.[id] INTO @temporary_inserted;'
            . 'SELECT * FROM @temporary_inserted;';
        $upsert['query without update part'][4] = 'SET NOCOUNT ON;'
            . 'DECLARE @temporary_inserted TABLE ([id] int);'
            . 'DECLARE @temp int;MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer]'
            . ' WHERE [name] = :qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS EXCLUDED'
            . ' ([email], [status]) ON ([T_upsert].[email]=EXCLUDED.[email])'
            . ' WHEN MATCHED THEN UPDATE SET @temp=1'
            . ' WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES (EXCLUDED.[email], EXCLUDED.[status])'
            . ' OUTPUT INSERTED.[id] INTO @temporary_inserted;'
            . 'SELECT * FROM @temporary_inserted;';
        $upsert['values and expressions without update part'][4] = 'SET NOCOUNT ON;'
            . 'DECLARE @temporary_inserted TABLE ([id] int);'
            . 'DECLARE @temp int;'
            . 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS EXCLUDED'
            . ' ([email], [ts]) ON ([T_upsert].[email]=EXCLUDED.[email])'
            . ' WHEN MATCHED THEN UPDATE SET @temp=1'
            . ' WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES (EXCLUDED.[email], EXCLUDED.[ts])'
            . ' OUTPUT INSERTED.[id] INTO @temporary_inserted;'
            . 'SELECT * FROM @temporary_inserted;';
        $upsert['query, values and expressions without update part'][4] = 'SET NOCOUNT ON;'
            . 'DECLARE @temporary_inserted TABLE ([id] int);'
            . 'DECLARE @temp int;'
            . 'MERGE [T_upsert] WITH (HOLDLOCK)'
            . ' USING (SELECT :phEmail AS [email], CONVERT(bigint, CURRENT_TIMESTAMP) AS [[ts]]) AS EXCLUDED'
            . ' ([email], [ts]) ON ([T_upsert].[email]=EXCLUDED.[email])'
            . ' WHEN MATCHED THEN UPDATE SET @temp=1'
            . ' WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES (EXCLUDED.[email], EXCLUDED.[ts])'
            . ' OUTPUT INSERTED.[id] INTO @temporary_inserted;'
            . 'SELECT * FROM @temporary_inserted;';
        $upsert['no unique columns in table - simple insert'][4] = 'SET NOCOUNT ON;'
            . 'DECLARE @temporary_inserted TABLE ([id] int);'
            . 'INSERT INTO {{%animal}} ([type]) OUTPUT INSERTED.[id] INTO @temporary_inserted VALUES (:qp0);'
            . 'SELECT * FROM @temporary_inserted;';

        $upsert['no columns to update'][3] = ['a'];
        $upsert['no columns to update'][4] = 'SET NOCOUNT ON;'
            . 'DECLARE @temporary_inserted TABLE ([a] int);'
            . 'DECLARE @temp int;'
            . 'MERGE [T_upsert_1] WITH (HOLDLOCK) USING (VALUES (1)) AS EXCLUDED'
            . ' ([a]) ON ([T_upsert_1].[a]=EXCLUDED.[a])'
            . ' WHEN MATCHED THEN UPDATE SET @temp=1'
            . ' WHEN NOT MATCHED THEN INSERT ([a]) VALUES (EXCLUDED.[a])'
            . ' OUTPUT INSERTED.[a] INTO @temporary_inserted;'
            . 'SELECT * FROM @temporary_inserted;';
        $upsert['no columns to update with unique'][4] = 'SET NOCOUNT ON;'
            . 'DECLARE @temporary_inserted TABLE ([id] int);'
            . 'DECLARE @temp int;'
            . 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0)) AS EXCLUDED'
            . ' ([email]) ON ([T_upsert].[email]=EXCLUDED.[email])'
            . ' WHEN MATCHED THEN UPDATE SET @temp=1'
            . ' WHEN NOT MATCHED THEN INSERT ([email]) VALUES (EXCLUDED.[email])'
            . ' OUTPUT INSERTED.[id] INTO @temporary_inserted;'
            . 'SELECT * FROM @temporary_inserted;';

        return [
            ...$upsert,
            'composite primary key' => [
                'notauto_pk',
                ['id_1' => 1, 'id_2' => 2.5, 'type' => 'Test'],
                true,
                ['id_1', 'id_2'],
                'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id_1] int, [id_2] decimal(5,2));'
                . 'MERGE [notauto_pk] WITH (HOLDLOCK) USING (VALUES (1, 2.5, :qp0)) AS EXCLUDED'
                . ' ([id_1], [id_2], [type]) ON (([notauto_pk].[id_1]=EXCLUDED.[id_1])'
                . ' AND ([notauto_pk].[id_2]=EXCLUDED.[id_2])) WHEN MATCHED THEN UPDATE SET [type]=EXCLUDED.[type]'
                . ' WHEN NOT MATCHED THEN INSERT ([id_1], [id_2], [type])'
                . ' VALUES (EXCLUDED.[id_1], EXCLUDED.[id_2], EXCLUDED.[type])'
                . ' OUTPUT INSERTED.[id_1],INSERTED.[id_2] INTO @temporary_inserted;SELECT * FROM @temporary_inserted;',
                [':qp0' => new Param('Test', DataType::STRING)],
            ],
            'no return columns' => [
                'type',
                ['int_col' => 3, 'char_col' => 'a', 'float_col' => 1.2, 'bool_col' => true],
                true,
                [],
                'INSERT INTO [type] ([int_col], [char_col], [float_col], [bool_col]) VALUES (3, :qp0, 1.2, 1)',
                [':qp0' => new Param('a', DataType::STRING)],
            ],
            'no return columns, table with pk' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'test address', 'status' => 1, 'profile_id' => 1],
                true,
                [],
                'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, 1, 1)) AS EXCLUDED'
                . ' ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=EXCLUDED.[email])'
                . ' WHEN MATCHED THEN UPDATE SET [address]=EXCLUDED.[address], [status]=EXCLUDED.[status],'
                . ' [profile_id]=EXCLUDED.[profile_id]'
                . ' WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id])'
                . ' VALUES (EXCLUDED.[email], EXCLUDED.[address], EXCLUDED.[status], EXCLUDED.[profile_id]);',
                [
                    ':qp0' => new Param('test@example.com', DataType::STRING),
                    ':qp1' => new Param('test address', DataType::STRING),
                ],
            ],
            'return all columns' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'test address', 'status' => 1, 'profile_id' => 1],
                true,
                null,
                'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int, [ts] int NULL, [email] varchar(128),'
                . ' [recovery_email] varchar(128) NULL, [address] text NULL, [status] tinyint, [orders] int,'
                . ' [profile_id] int NULL);MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, 1, 1))'
                . ' AS EXCLUDED ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=EXCLUDED.[email])'
                . ' WHEN MATCHED THEN UPDATE SET [address]=EXCLUDED.[address], [status]=EXCLUDED.[status],'
                . ' [profile_id]=EXCLUDED.[profile_id] WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id])'
                . ' VALUES (EXCLUDED.[email], EXCLUDED.[address], EXCLUDED.[status], EXCLUDED.[profile_id])'
                . ' OUTPUT INSERTED.[id],INSERTED.[ts],INSERTED.[email],INSERTED.[recovery_email],INSERTED.[address],'
                . 'INSERTED.[status],INSERTED.[orders],INSERTED.[profile_id] INTO @temporary_inserted;'
                . 'SELECT * FROM @temporary_inserted;',
                [
                    ':qp0' => new Param('test@example.com', DataType::STRING),
                    ':qp1' => new Param('test address', DataType::STRING),
                ],
            ],
        ];
    }

    public static function buildColumnDefinition(): array
    {
        $values = parent::buildColumnDefinition();

        $values[PseudoType::PK][0] = 'int IDENTITY PRIMARY KEY';
        $values[PseudoType::UPK][0] = 'int IDENTITY PRIMARY KEY';
        $values[PseudoType::BIGPK][0] = 'bigint IDENTITY PRIMARY KEY';
        $values[PseudoType::UBIGPK][0] = 'bigint IDENTITY PRIMARY KEY';
        $values[PseudoType::UUID_PK][0] = 'uniqueidentifier PRIMARY KEY DEFAULT newid()';
        $values[PseudoType::UUID_PK_SEQ][0] = 'uniqueidentifier PRIMARY KEY DEFAULT newsequentialid()';
        $values['STRING'][0] = 'nvarchar(255)';
        $values['STRING(100)'][0] = 'nvarchar(100)';
        $values['primaryKey()'][0] = 'int IDENTITY PRIMARY KEY';
        $values['primaryKey(false)'][0] = 'int PRIMARY KEY';
        $values['smallPrimaryKey()'][0] = 'smallint IDENTITY PRIMARY KEY';
        $values['smallPrimaryKey(false)'][0] = 'smallint PRIMARY KEY';
        $values['bigPrimaryKey()'][0] = 'bigint IDENTITY PRIMARY KEY';
        $values['bigPrimaryKey(false)'][0] = 'bigint PRIMARY KEY';
        $values['uuidPrimaryKey()'][0] = 'uniqueidentifier PRIMARY KEY DEFAULT newid()';
        $values['uuidPrimaryKey(false)'][0] = 'uniqueidentifier PRIMARY KEY';
        $values['boolean()'][0] = 'bit';
        $values['boolean(100)'][0] = 'bit';
        $values['bit()'][0] = 'bigint';
        $values['bit(1)'][0] = 'bit';
        $values['bit(8)'][0] = 'tinyint';
        $values['bit(64)'][0] = 'bigint';
        $values['tinyint(2)'][0] = 'tinyint';
        $values['smallint(4)'][0] = 'smallint';
        $values['integer()'][0] = 'int';
        $values['integer(8)'][0] = 'int';
        $values['bigint(15)'][0] = 'bigint';
        $values['float()'][0] = 'real';
        $values['float(10)'][0] = 'real';
        $values['float(10,2)'][0] = 'real';
        $values['double()'][0] = 'float(53)';
        $values['double(10)'][0] = 'float(53)';
        $values['double(10,2)'][0] = 'float(53)';
        $values['char()'][0] = 'nchar(1)';
        $values['char(10)'][0] = 'nchar(10)';
        $values['char(null)'][0] = 'nchar';
        $values['string()'][0] = 'nvarchar(255)';
        $values['string(100)'][0] = 'nvarchar(100)';
        $values['string(null)'][0] = 'nvarchar(255)';
        $values['text()'][0] = 'nvarchar(max)';
        $values['text(1000)'][0] = 'nvarchar(max)';
        $values['binary()'][0] = 'varbinary(max)';
        $values['binary(1000)'][0] = 'varbinary(max)';
        $values['timestamp()'][0] = 'datetime2(0)';
        $values['timestamp(6)'][0] = 'datetime2(6)';
        $values['timestamp(null)'][0] = 'datetime2';
        $values['datetime()'][0] = 'datetime2(0)';
        $values['datetime(6)'][0] = 'datetime2(6)';
        $values['datetime(null)'][0] = 'datetime2';
        $values['datetimeWithTimezone()'][0] = 'datetimeoffset(0)';
        $values['datetimeWithTimezone(6)'][0] = 'datetimeoffset(6)';
        $values['datetimeWithTimezone(null)'][0] = 'datetimeoffset';
        $values['timeWithTimezone()'][0] = 'time(0)';
        $values['timeWithTimezone(6)'][0] = 'time(6)';
        $values['timeWithTimezone(null)'][0] = 'time';
        $values['uuid()'][0] = 'uniqueidentifier';
        $values['array()'] = [
            'nvarchar(max) CHECK (isjson([array_col]) > 0)',
            $values['array()'][1]->withName('array_col'),
        ];
        $values['structured()'] = [
            'nvarchar(max) CHECK (isjson([structured_col]) > 0)',
            $values['structured()'][1]->withName('structured_col'),
        ];
        $values["structured('json')"] = ['nvarchar(max)', ColumnBuilder::structured('nvarchar(max)')];
        $values['json()'] = [
            'nvarchar(max) CHECK (isjson([json_col]) > 0)',
            $values['json()'][1]->withName('json_col'),
        ];
        $values['json(100)'] = [
            'nvarchar(max) CHECK (isjson([json_100]) > 0)',
            $values['json(100)'][1]->withName('json_100'),
        ];
        $values["extra('NOT NULL')"][0] = 'nvarchar(255) NOT NULL';
        $values["extra('')"][0] = 'nvarchar(255)';
        $values["check('value > 5')"][0] = 'int CHECK ([check_col] > 5)';
        $values["check('')"][0] = 'int';
        $values['check(null)'][0] = 'int';
        $values["collation('collation_name')"] = [
            'nvarchar(255) COLLATE Latin1_General_CI_AS',
            ColumnBuilder::string()->collation('Latin1_General_CI_AS'),
        ];
        $values["collation('')"][0] = 'nvarchar(255)';
        $values['collation(null)'][0] = 'nvarchar(255)';
        $values["comment('comment')"][0] = 'nvarchar(255)';
        $values["comment('')"][0] = 'nvarchar(255)';
        $values['comment(null)'][0] = 'nvarchar(255)';
        $values["defaultValue('value')"][0] = "nvarchar(255) DEFAULT 'value'";
        $values["defaultValue('')"][0] = "nvarchar(255) DEFAULT ''";
        $values['defaultValue(null)'][0] = 'nvarchar(255) DEFAULT NULL';
        $values['defaultValue($expression)'][0] = 'int DEFAULT (1 + 2)';
        $values['defaultValue($emptyExpression)'][0] = 'int';
        $values["integer()->defaultValue('')"][0] = 'int DEFAULT NULL';
        $values['notNull()'][0] = 'nvarchar(255) NOT NULL';
        $values['null()'][0] = 'nvarchar(255) NULL';
        $values['integer()->primaryKey()'][0] = 'int PRIMARY KEY';
        $values['string()->primaryKey()'][0] = 'nvarchar(255) PRIMARY KEY';
        $values['size(10)'][0] = 'nvarchar(10)';
        $values['unique()'][0] = 'nvarchar(255) UNIQUE';
        $values['unsigned()'][0] = 'int';
        $values['integer(8)->scale(2)'][0] = 'int';
        $values['reference($reference)'][0] = 'int REFERENCES [ref_table] ([id]) ON DELETE SET NULL ON UPDATE CASCADE';
        $values['reference($referenceWithSchema)'][0] = 'int REFERENCES [ref_schema].[ref_table] ([id]) ON DELETE SET NULL ON UPDATE CASCADE';

        $referenceRestrict = new ForeignKey(
            foreignTableName: 'ref_table',
            foreignColumnNames: ['id'],
            onDelete: ReferentialAction::RESTRICT,
            onUpdate: ReferentialAction::RESTRICT,
        );

        $values[] = ['int REFERENCES [ref_table] ([id])', ColumnBuilder::integer()->reference($referenceRestrict)];

        return $values;
    }

    public static function buildValue(): array
    {
        $values = parent::buildValue();

        $values['true'][1] = '1';
        $values['false'][1] = '0';

        return $values;
    }

    public static function prepareParam(): array
    {
        $values = parent::prepareParam();

        $values['true'][0] = '1';
        $values['false'][0] = '0';

        return $values;
    }

    public static function prepareValue(): array
    {
        $values = parent::prepareValue();

        $values['true'][0] = '1';
        $values['false'][0] = '0';

        return $values;
    }

    public static function caseXBuilder(): array
    {
        $data = parent::caseXBuilder();

        unset($data['with case condition']);

        return $data;
    }

    public static function delete(): array
    {
        $values = parent::delete();
        $values['base'][2] = 'DELETE FROM [user] WHERE ([is_enabled] = 0) AND ([power] = WRONG_POWER())';
        return $values;
    }

    public static function lengthBuilder(): array
    {
        $data = parent::lengthBuilder();

        foreach ($data as &$value) {
            $value[1] = str_replace('LENGTH(', 'LEN(', $value[1]);
        }

        return $data;
    }

    public static function multiOperandFunctionClasses(): array
    {
        return [
            ...parent::multiOperandFunctionClasses(),
            ArrayMerge::class => [ArrayMerge::class],
        ];
    }

    public static function multiOperandFunctionBuilder(): array
    {
        $data = parent::multiOperandFunctionBuilder();

        $db = self::getDb();
        $serverVersion = $db->getServerInfo()->getVersion();
        $db->close();

        if (version_compare($serverVersion, '16', '<')) {
            $data['Greatest with 2 operands'][2] = '(SELECT MAX(value) FROM (SELECT 1 AS value UNION SELECT 1 + 2 AS value) AS t)';
            $data['Greatest with 4 operands'][2] = '(SELECT MAX(value) FROM (SELECT 1 AS value UNION SELECT 1.5 AS value UNION SELECT 1 + 2 AS value UNION SELECT (SELECT 10) AS value) AS t)';
            $data['Least with 2 operands'][2] = '(SELECT MIN(value) FROM (SELECT 1 AS value UNION SELECT 1 + 2 AS value) AS t)';
            $data['Least with 4 operands'][2] = '(SELECT MIN(value) FROM (SELECT 1 AS value UNION SELECT 1.5 AS value UNION SELECT 1 + 2 AS value UNION SELECT (SELECT 10) AS value) AS t)';
        }

        $data['Longest with 2 operands'][2] = "(SELECT TOP 1 value FROM (SELECT :qp0 AS value UNION SELECT :qp1 AS value) AS t ORDER BY LEN(value) DESC)";
        $data['Longest with 3 operands'][2] = "(SELECT TOP 1 value FROM (SELECT :qp0 AS value UNION SELECT (SELECT 'longest') AS value UNION SELECT :qp1 AS value) AS t ORDER BY LEN(value) DESC)";
        $data['Shortest with 2 operands'][2] = "(SELECT TOP 1 value FROM (SELECT :qp0 AS value UNION SELECT :qp1 AS value) AS t ORDER BY LEN(value) ASC)";
        $data['Shortest with 3 operands'][2] = "(SELECT TOP 1 value FROM (SELECT :qp0 AS value UNION SELECT (SELECT 'longest') AS value UNION SELECT :qp1 AS value) AS t ORDER BY LEN(value) ASC)";

        $stringParam = new Param('[3,4,5]', DataType::STRING);

        return [
            ...$data,
            'ArrayMerge with 1 operand' => [
                ArrayMerge::class,
                [[1, 2, 3]],
                '(:qp0)',
                [1, 2, 3],
                [':qp0' => new Param('[1,2,3]', DataType::STRING)],
            ],
            'ArrayMerge with 2 operands' => [
                ArrayMerge::class,
                [[1, 2, 3], $stringParam],
                <<<SQL
                (SELECT '[' + STRING_AGG('"' + STRING_ESCAPE(value, 'json') + '"', ',') + ']' AS value FROM (SELECT value FROM OPENJSON(:qp0) UNION SELECT value FROM OPENJSON(:qp1)) AS t)
                SQL,
                [1, 2, 3, 4, 5],
                [
                    ':qp0' => new Param('[1,2,3]', DataType::STRING),
                    ':qp1' => $stringParam,
                ],
            ],
            'ArrayMerge with 4 operands' => [
                ArrayMerge::class,
                [[1, 2, 3], new ArrayValue([5, 6, 7]), $stringParam, self::getDb()->select(new ArrayValue([9, 10]))],
                <<<SQL
                (SELECT '[' + STRING_AGG('"' + STRING_ESCAPE(value, 'json') + '"', ',') + ']' AS value FROM (SELECT value FROM OPENJSON(:qp0) UNION SELECT value FROM OPENJSON(:qp1) UNION SELECT value FROM OPENJSON(:qp2) UNION SELECT value FROM OPENJSON((SELECT :qp3))) AS t)
                SQL,
                [1, 2, 3, 4, 5, 6, 7, 9, 10],
                [
                    ':qp0' => new Param('[1,2,3]', DataType::STRING),
                    ':qp1' => new Param('[5,6,7]', DataType::STRING),
                    ':qp2' => $stringParam,
                    ':qp3' => new Param('[9,10]', DataType::STRING),
                ],
            ],
        ];
    }

    public static function upsertWithMultiOperandFunctions(): array
    {
        $data = parent::upsertWithMultiOperandFunctions();

        $db = self::getDb();
        $serverVersion = $db->getServerInfo()->getVersion();
        $db->close();

        if (version_compare($serverVersion, '16', '<')) {
            $data[0][3] = 'MERGE [test_upsert_with_functions] WITH (HOLDLOCK)'
                . ' USING (VALUES (1, :qp0, 5, 5, :qp1, :qp2)) AS EXCLUDED'
                . ' ([id], [array_col], [greatest_col], [least_col], [longest_col], [shortest_col])'
                . ' ON ([test_upsert_with_functions].[id]=EXCLUDED.[id]) WHEN MATCHED THEN UPDATE SET'
                . " [array_col]=(SELECT '[' + STRING_AGG('\"' + STRING_ESCAPE(value, 'json') + '\"', ',') WITHIN GROUP (ORDER BY value) + ']' AS value FROM (SELECT value FROM OPENJSON([test_upsert_with_functions].[array_col]) UNION SELECT value FROM OPENJSON(EXCLUDED.[array_col])) AS t),"
                . ' [greatest_col]=(SELECT MAX(value) FROM (SELECT [test_upsert_with_functions].[greatest_col] AS value UNION SELECT EXCLUDED.[greatest_col] AS value) AS t),'
                . ' [least_col]=(SELECT MIN(value) FROM (SELECT [test_upsert_with_functions].[least_col] AS value UNION SELECT EXCLUDED.[least_col] AS value) AS t),'
                . ' [longest_col]=(SELECT TOP 1 value FROM (SELECT [test_upsert_with_functions].[longest_col] AS value UNION SELECT EXCLUDED.[longest_col] AS value) AS t ORDER BY LEN(value) DESC),'
                . ' [shortest_col]=(SELECT TOP 1 value FROM (SELECT [test_upsert_with_functions].[shortest_col] AS value UNION SELECT EXCLUDED.[shortest_col] AS value) AS t ORDER BY LEN(value) ASC)'
                . ' WHEN NOT MATCHED THEN INSERT ([id], [array_col], [greatest_col], [least_col], [longest_col], [shortest_col])'
                . ' VALUES (EXCLUDED.[id], EXCLUDED.[array_col], EXCLUDED.[greatest_col], EXCLUDED.[least_col], EXCLUDED.[longest_col], EXCLUDED.[shortest_col]);';
        } else {
            $data[0][3] = 'MERGE [test_upsert_with_functions] WITH (HOLDLOCK)'
                . ' USING (VALUES (1, :qp0, 5, 5, :qp1, :qp2)) AS EXCLUDED'
                . ' ([id], [array_col], [greatest_col], [least_col], [longest_col], [shortest_col])'
                . ' ON ([test_upsert_with_functions].[id]=EXCLUDED.[id]) WHEN MATCHED THEN UPDATE SET'
                . " [array_col]=(SELECT '[' + STRING_AGG('\"' + STRING_ESCAPE(value, 'json') + '\"', ',') WITHIN GROUP (ORDER BY value) + ']' AS value FROM (SELECT value FROM OPENJSON([test_upsert_with_functions].[array_col]) UNION SELECT value FROM OPENJSON(EXCLUDED.[array_col])) AS t),"
                . ' [greatest_col]=GREATEST([test_upsert_with_functions].[greatest_col], EXCLUDED.[greatest_col]),'
                . ' [least_col]=LEAST([test_upsert_with_functions].[least_col], EXCLUDED.[least_col]),'
                . ' [longest_col]=(SELECT TOP 1 value FROM (SELECT [test_upsert_with_functions].[longest_col] AS value UNION SELECT EXCLUDED.[longest_col] AS value) AS t ORDER BY LEN(value) DESC),'
                . ' [shortest_col]=(SELECT TOP 1 value FROM (SELECT [test_upsert_with_functions].[shortest_col] AS value UNION SELECT EXCLUDED.[shortest_col] AS value) AS t ORDER BY LEN(value) ASC)'
                . ' WHEN NOT MATCHED THEN INSERT ([id], [array_col], [greatest_col], [least_col], [longest_col], [shortest_col])'
                . ' VALUES (EXCLUDED.[id], EXCLUDED.[array_col], EXCLUDED.[greatest_col], EXCLUDED.[least_col], EXCLUDED.[longest_col], EXCLUDED.[shortest_col]);';
        }
        $data[0][4]['array_col'] = '["1","2","3","4","5"]';

        return $data;
    }
}
