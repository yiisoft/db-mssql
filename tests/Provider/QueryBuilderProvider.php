<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\TestCase;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\TestSupport\Provider\QueryBuilderProvider as BaseQueryBuilderProvider;
use Yiisoft\Db\TestSupport\TraversableObject;

final class QueryBuilderProvider extends TestCase
{
    /**
     * @var string ` ESCAPE 'char'` part of a LIKE condition SQL.
     */
    protected string $likeEscapeCharSql = '';

    /**
     * @var array map of values to their replacements in LIKE query params.
     */
    protected array $likeParameterReplacements = [
        '\%' => '[%]',
        '\_' => '[_]',
        '[' => '[[]',
        ']' => '[]]',
        '\\\\' => '[\\]',
    ];

    public function addDropChecksProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_check';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->dropCheck($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] CHECK ([[C_not_null]] > 100)",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->addCheck($name, $tableName, '[[C_not_null]] > 100');
                },
            ],
        ];
    }

    public function addDropForeignKeysProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->addDropForeignKeysProvider();
    }

    public function addDropPrimaryKeysProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->addDropPrimaryKeysProvider();
    }

    public function addDropUniquesProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->addDropUniquesProvider();
    }

    public function batchInsertProvider(): array
    {
        $data = (new BaseQueryBuilderProvider($this->getConnection()))->batchInsertProvider();
        $data['escape-danger-chars']['expected'] = 'INSERT INTO [customer] ([address])'
            . " VALUES ('SQL-danger chars are escaped: ''); --')";
        $data['bool-false, bool2-null']['expected'] = 'INSERT INTO [type] ([bool_col], [bool_col2]) VALUES (0, NULL)';
        $data['bool-false, time-now()']['expected'] = 'INSERT INTO {{%type}} ({{%type}}.[[bool_col]], [[time]])'
            . ' VALUES (0, now())';

        return $data;
    }

    public function buildConditionsProvider(): array
    {
        $data = (new BaseQueryBuilderProvider($this->getConnection()))->buildConditionsProvider();

        $data['composite in'] = [
            ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
            '(([id] = :qp0 AND [name] = :qp1))',
            [':qp0' => 1, ':qp1' => 'oy'],
        ];
        $data['composite in using array objects'] = [
            ['in', new TraversableObject(['id', 'name']), new TraversableObject([
                ['id' => 1, 'name' => 'oy'],
                ['id' => 2, 'name' => 'yo'],
            ])],
            '(([id] = :qp0 AND [name] = :qp1) OR ([id] = :qp2 AND [name] = :qp3))',
            [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
        ];

        return $data;
    }

    public function buildExistsParamsProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->buildExistsParamsProvider();
    }

    public function buildFilterConditionProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->buildFilterConditionProvider();
    }

    public function buildFromDataProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->buildFromDataProvider();
    }

    public function buildLikeConditionsProvider(): array
    {
        return (new BaseQueryBuilderProvider(
            $this->getConnection(),
            $this->likeEscapeCharSql,
            $this->likeParameterReplacements
        ))->buildLikeConditionsProvider();
    }

    public function createDropIndexesProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->createDropIndexesProvider();
    }

    public function deleteProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->deleteProvider();
    }

    public function insertProvider(): array
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
                $this->replaceQuotes('SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);' .
                    'INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) OUTPUT INSERTED.* INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, :qp3, :qp4);' .
                    'SELECT * FROM @temporary_inserted'),
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                [
                    '{{%type}}.[[related_id]]' => null,
                    '[[time]]' => new Expression('now()'),
                ],
                [],
                'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([int_col] int , [int_col2] int NULL, [tinyint_col] tinyint NULL, [smallint_col] smallint NULL, [char_col] char(100) , [char_col2] varchar(100) NULL, [char_col3] text NULL, [float_col] decimal , [float_col2] float NULL, [blob_col] varbinary(MAX) NULL, [numeric_col] decimal NULL, [time] datetime , [bool_col] tinyint , [bool_col2] tinyint NULL);' .
                'INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) OUTPUT INSERTED.* INTO @temporary_inserted VALUES (:qp0, now());' .
                'SELECT * FROM @temporary_inserted',
                [
                    ':qp0' => null,
                ],
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
                $this->replaceQuotes('SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);' .
                    'INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]], [[col]]) OUTPUT INSERTED.* INTO @temporary_inserted VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar));' .
                    'SELECT * FROM @temporary_inserted'),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                (new Query($this->getConnection()))
                    ->select([
                        'email',
                        'name',
                        'address',
                        'is_active',
                        'related_id',
                    ])
                    ->from('customer')
                    ->where([
                        'email' => 'test@example.com',
                        'name' => 'sergeymakinen',
                        'address' => '{{city}}',
                        'is_active' => false,
                        'related_id' => null,
                        'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                    ]),
                [':phBar' => 'bar'],
                $this->replaceQuotes('SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);' .
                    'INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) OUTPUT INSERTED.* INTO @temporary_inserted SELECT [[email]], [[name]], [[address]], [[is_active]], [[related_id]] FROM [[customer]] WHERE ([[email]]=:qp1) AND ([[name]]=:qp2) AND ([[address]]=:qp3) AND ([[is_active]]=:qp4) AND ([[related_id]] IS NULL) AND ([[col]]=CONCAT(:phFoo, :phBar));' .
                    'SELECT * FROM @temporary_inserted'),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
        ];
    }

    public function updateProvider(): array
    {
        return [
            [
                'customer',
                [
                    'status' => 1,
                    'updated_at' => new Expression('now()'),
                ],
                [
                    'id' => 100,
                ],
                $this->replaceQuotes(
                    'UPDATE [[customer]] SET [[status]]=:qp0, [[updated_at]]=now() WHERE [[id]]=:qp1'
                ),
                [
                    ':qp0' => 1,
                    ':qp1' => 100,
                ],
            ],
        ];
    }

    public function upsertProvider(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email],'
                . ' [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN'
                . ' UPDATE SET [address]=[EXCLUDED].[address], [status]=[EXCLUDED].[status], [profile_id]'
                . '=[EXCLUDED].[profile_id] WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id])'
                . ' VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);',
            ],

            'regular values with update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email],'
                . ' [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN'
                . ' UPDATE SET [address]=:qp4, [status]=:qp5, [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN'
                . ' INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email],'
                . ' [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);',
            ],

            'regular values without update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email],'
                . ' [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN'
                . ' INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email],'
                . ' [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);',
            ],

            'query' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE'
                . ' [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email],'
                . ' [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET'
                . ' [status]=[EXCLUDED].[status] WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES'
                . ' ([EXCLUDED].[email], [EXCLUDED].[status]);',
            ],

            'query with update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE'
                . ' [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email],'
                . ' [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET'
                . ' [address]=:qp1, [status]=:qp2, [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN'
                . ' INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);',
            ],

            'query without update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE'
                . ' [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email],'
                . ' [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email],'
                . ' [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);',
            ],

            'values and expressions' => [
                3 => 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [ts] int NULL, [email] varchar(128)'
                . ' , [recovery_email] varchar(128) NULL, [address] text NULL, [status] tinyint , [orders] int ,'
                . ' [profile_id] int NULL);'
                . 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) OUTPUT INSERTED.*'
                . ' INTO @temporary_inserted VALUES (:qp0, now());'
                . 'SELECT * FROM @temporary_inserted',
            ],

            'values and expressions with update part' => [
                3 => 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [ts] int NULL, [email] varchar(128)'
                . ' , [recovery_email] varchar(128) NULL, [address] text NULL, [status] tinyint , [orders] int ,'
                . ' [profile_id] int NULL);'
                . 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) OUTPUT INSERTED.*'
                . ' INTO @temporary_inserted VALUES (:qp0, now());'
                . 'SELECT * FROM @temporary_inserted',
            ],

            'values and expressions without update part' => [
                3 => 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [ts] int NULL, [email] varchar(128)'
                . ' , [recovery_email] varchar(128) NULL, [address] text NULL, [status] tinyint , [orders] int ,'
                . ' [profile_id] int NULL);'
                . 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) OUTPUT INSERTED.*'
                . ' INTO @temporary_inserted VALUES (:qp0, now());'
                . 'SELECT * FROM @temporary_inserted',
            ],

            'query, values and expressions with update part' => [
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], now() AS [[time]]) AS'
                . ' [EXCLUDED] ([email], [[time]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN MATCHED THEN'
                . ' UPDATE SET [ts]=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email],'
                . ' [[time]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[time]]);',
            ],

            'query, values and expressions without update part' => [
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], now() AS [[time]])'
                . ' AS [EXCLUDED] ([email], [[time]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN MATCHED THEN'
                . ' UPDATE SET [ts]=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email],'
                . ' [[time]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[time]]);',
            ],
            'no columns to update' => [
                3 => 'MERGE [T_upsert_1] WITH (HOLDLOCK) USING (VALUES (:qp0)) AS [EXCLUDED] ([a]) ON'
                . ' ([T_upsert_1].[a]=[EXCLUDED].[a]) WHEN NOT MATCHED THEN INSERT ([a]) VALUES ([EXCLUDED].[a]);',
            ],
        ];

        $newData = (new BaseQueryBuilderProvider($this->getConnection()))->upsertProvider();

        foreach ($concreteData as $testName => $data) {
            $newData[$testName] = array_replace($newData[$testName], $data);
        }

        return $newData;
    }
}
