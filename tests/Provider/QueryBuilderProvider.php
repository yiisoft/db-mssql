<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Conditions\InCondition;
use Yiisoft\Db\Tests\Provider\BaseQueryBuilderProvider;
use Yiisoft\Db\Tests\Support\TraversableObject;

final class QueryBuilderProvider
{
    use TestTrait;

    /**
     * @throws Exception
     */
    public function alterColumn(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $alterColumn = $baseQueryBuilderProvider->alterColumn($this->getConnection());

        $alterColumn[0][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] varchar(255)
        SQL;
        $alterColumn[1][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] SET NOT null
        SQL;
        $alterColumn[2][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] drop default
        SQL;
        $alterColumn[3][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] reset xyz
        SQL;
        $alterColumn[4][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255)
        SQL;
        $alterColumn[5][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] varchar(255) USING bar::varchar
        SQL;
        $alterColumn[6][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] varchar(255) using cast("bar" as varchar)
        SQL;
        $alterColumn[7][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) NOT NULL
        SQL;
        $alterColumn[8][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) NULL DEFAULT NULL
        SQL;
        $alterColumn[9][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) NULL DEFAULT 'xxx'
        SQL;
        $alterColumn[10][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) CHECK (char_length(bar) > 5)
        SQL;
        $alterColumn[11][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) DEFAULT ''
        SQL;
        $alterColumn[12][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(255) DEFAULT 'AbCdE'
        SQL;
        $alterColumn[13][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] datetime DEFAULT CURRENT_TIMESTAMP
        SQL;
        $alterColumn[14][3] = <<<SQL
        ALTER TABLE [foo1] ALTER COLUMN [bar] nvarchar(30) UNIQUE
        SQL;

        return $alterColumn;
    }

    /**
     * @throws Exception
     */
    public function batchInsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->batchInsert($this->getConnection());
    }

    /**
     * @throws Exception
     */
    public function buildConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $buildConditions = $baseQueryBuilderProvider->buildConditions($this->getConnection());

        $buildConditions['inCondition-custom-1'] = [new InCondition(['id', 'name'], 'in', []), '()', []];
        $buildConditions['inCondition-custom-3'] = [
            new InCondition(['id', 'name'], 'in', [['id' => 1]]),
            '(([id] = :qp0 AND [name] IS NULL))',
            [':qp0' => 1],
        ];
        $buildConditions['inCondition-custom-4'] = [
            new InCondition(['id', 'name'], 'in', [['name' => 'oy']]),
            '(([id] IS NULL AND [name] = :qp0))',
            [':qp0' => 'oy'],
        ];

        $buildConditions['inCondition-custom-5'] = [
            new InCondition(['id', 'name'], 'in', [['id' => 1, 'name' => 'oy']]),
            '(([id] = :qp0 AND [name] = :qp1))',
            [':qp0' => 1, ':qp1' => 'oy'],
        ];
        $buildConditions['composite in'] = [
            ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
            '(([id] = :qp0 AND [name] = :qp1))',
            [':qp0' => 1, ':qp1' => 'oy'],
        ];
        $buildConditions['composite in using array objects'] = [
            ['in', new TraversableObject(['id', 'name']), new TraversableObject([
                ['id' => 1, 'name' => 'oy'],
                ['id' => 2, 'name' => 'yo'],
            ])],
            '(([id] = :qp0 AND [name] = :qp1) OR ([id] = :qp2 AND [name] = :qp3))',
            [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
        ];

        unset($buildConditions['inCondition-custom-2']);

        return $buildConditions;
    }

    /**
     * @throws Exception
     */
    public function buildLikeConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();
        return $baseQueryBuilderProvider->buildLikeConditions(
            $this->getConnection(),
            '',
            [
                '\%' => '[%]',
                '\_' => '[_]',
                '[' => '[[]',
                ']' => '[]]',
                '\\\\' => '[\\]'
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildWhereExists($this->getConnection());
    }


    /**
     * @throws Exception
     */
    public function delete(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->delete($this->getConnection());
    }

    /**
     * @throws Exception
     */
    public function insert(): array
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
                INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
                SQL,
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
                ['{{%type}}.[[related_id]]' => null, '[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) VALUES (:qp0, now())
                SQL,
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
                <<<SQL
                INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id], [col]) VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar))
                SQL,
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
                <<<SQL
                INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) SELECT [email], [name], [address], [is_active], [related_id] FROM [customer] WHERE ([email]=:qp1) AND ([name]=:qp2) AND ([address]=:qp3) AND ([is_active]=:qp4) AND ([related_id] IS NULL) AND ([col]=CONCAT(:phFoo, :phBar))
                SQL,
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

    /**
     * @throws Exception
     */
    public function insertEx(): array
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);
                SQL .
                <<<SQL
                INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id],INSERTED.[email],INSERTED.[name],INSERTED.[address],INSERTED.[status],INSERTED.[profile_id] INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, :qp3, :qp4);
                SQL .
                <<<SQL
                SELECT * FROM @temporary_inserted;
                SQL,
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
                ['{{%type}}.[[related_id]]' => null, '[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([int_col] int , [int_col2] int NULL, [tinyint_col] tinyint NULL, [smallint_col] smallint NULL, [char_col] char(100) , [char_col2] varchar(100) NULL, [char_col3] text NULL, [float_col] decimal , [float_col2] float NULL, [blob_col] varbinary(MAX) NULL, [numeric_col] decimal NULL, [time] datetime , [bool_col] tinyint , [bool_col2] tinyint NULL);
                SQL .
                <<<SQL
                INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) OUTPUT INSERTED.[int_col],INSERTED.[int_col2],INSERTED.[tinyint_col],INSERTED.[smallint_col],INSERTED.[char_col],INSERTED.[char_col2],INSERTED.[char_col3],INSERTED.[float_col],INSERTED.[float_col2],INSERTED.[blob_col],INSERTED.[numeric_col],INSERTED.[time],INSERTED.[bool_col],INSERTED.[bool_col2] INTO @temporary_inserted VALUES (:qp0, now());
                SQL .
                <<<SQL
                SELECT * FROM @temporary_inserted;
                SQL,
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
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);
                SQL .
                <<<SQL
                INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id], [col]) OUTPUT INSERTED.[id],INSERTED.[email],INSERTED.[name],INSERTED.[address],INSERTED.[status],INSERTED.[profile_id] INTO @temporary_inserted VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar));
                SQL .
                <<<SQL
                SELECT * FROM @temporary_inserted;
                SQL,
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
                    ->select(['email', 'name', 'address', 'is_active', 'related_id'])
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
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);
                SQL .
                <<<SQL
                INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id],INSERTED.[email],INSERTED.[name],INSERTED.[address],INSERTED.[status],INSERTED.[profile_id] INTO @temporary_inserted SELECT [email], [name], [address], [is_active], [related_id] FROM [customer] WHERE ([email]=:qp1) AND ([name]=:qp2) AND ([address]=:qp3) AND ([is_active]=:qp4) AND ([related_id] IS NULL) AND ([col]=CONCAT(:phFoo, :phBar));
                SQL .
                <<<SQL
                SELECT * FROM @temporary_inserted;
                SQL,
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

    public function selectExist(): array
    {
        return [
            [
                <<<SQL
                SELECT 1 FROM `table` WHERE `id` = 1
                SQL,
                <<<SQL
                SELECT CASE WHEN EXISTS(SELECT 1 FROM `table` WHERE `id` = 1) THEN 1 ELSE 0 END
                SQL,
            ],
        ];
    }

    public function update(): array
    {
        return [
            [
                'customer',
                ['status' => 1, 'updated_at' => new Expression('now()')],
                ['id' => 100],
                <<<SQL
                UPDATE [customer] SET [status]=:qp0, [updated_at]=now() WHERE [id]=:qp1
                SQL,
                [':qp0' => 1, ':qp1' => 100],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [address]=[EXCLUDED].[address], [status]=[EXCLUDED].[status], [profile_id]=[EXCLUDED].[profile_id] WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);
                SQL,
            ],

            'regular values with update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [address]=:qp4, [status]=:qp5, [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);
                SQL,
            ],

            'regular values without update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);
                SQL,
            ],

            'query' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [status]=[EXCLUDED].[status] WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);
                SQL,
            ],

            'query with update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [address]=:qp1, [status]=:qp2, [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);
                SQL,
            ],

            'query without update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);
                SQL,
            ],

            'values and expressions' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],

            'values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],

            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],

            'query, values and expressions with update part' => [
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], now() AS [[time]]) AS [EXCLUDED] ([email], [[time]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [ts]=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [[time]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[time]]);
                SQL,
            ],

            'query, values and expressions without update part' => [
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], now() AS [[time]]) AS [EXCLUDED] ([email], [[time]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [ts]=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [[time]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[time]]);
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                MERGE [T_upsert_1] WITH (HOLDLOCK) USING (VALUES (:qp0)) AS [EXCLUDED] ([a]) ON ([T_upsert_1].[a]=[EXCLUDED].[a]) WHEN NOT MATCHED THEN INSERT ([a]) VALUES ([EXCLUDED].[a]);
                SQL,
            ],
        ];

        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();
        $upsert = $baseQueryBuilderProvider->upsert($this->getConnection());

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }
}
