<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\Tests\Provider\AbstractQueryBuilderProvider;
use Yiisoft\Db\Tests\Support\TraversableObject;

use function array_replace;

final class QueryBuilderProvider extends AbstractQueryBuilderProvider
{
    use TestTrait;

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

    public function buildCondition(): array
    {
        $buildCondition = parent::buildCondition();

        $buildCondition['inCondition-custom-1'] = [new InCondition(['id', 'name'], 'in', []), '()', []];
        $buildCondition['inCondition-custom-3'] = [
            new InCondition(['id', 'name'], 'in', [['id' => 1]]),
            '(([id] = :qp0 AND [name] IS NULL))',
            [':qp0' => 1],
        ];
        $buildCondition['inCondition-custom-4'] = [
            new InCondition(['id', 'name'], 'in', [['name' => 'oy']]),
            '(([id] IS NULL AND [name] = :qp0))',
            [':qp0' => 'oy'],
        ];

        $buildCondition['inCondition-custom-5'] = [
            new InCondition(['id', 'name'], 'in', [['id' => 1, 'name' => 'oy']]),
            '(([id] = :qp0 AND [name] = :qp1))',
            [':qp0' => 1, ':qp1' => 'oy'],
        ];
        $buildCondition['composite in'] = [
            ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
            '(([id] = :qp0 AND [name] = :qp1))',
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

        unset($buildCondition['inCondition-custom-2']);

        return $buildCondition;
    }

    public function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO [customer] DEFAULT VALUES
        SQL;

        return $insert;
    }

    public function insertEx(): array
    {
        $db = $this->getConnection();

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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id],INSERTED.[email],INSERTED.[name],INSERTED.[address],INSERTED.[status],INSERTED.[profile_id] INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, :qp3, :qp4);SELECT * FROM @temporary_inserted;
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([int_col] int , [int_col2] int NULL, [tinyint_col] tinyint NULL, [smallint_col] smallint NULL, [char_col] char(100) , [char_col2] varchar(100) NULL, [char_col3] text NULL, [float_col] decimal , [float_col2] float NULL, [blob_col] varbinary(MAX) NULL, [numeric_col] decimal NULL, [time] datetime , [bool_col] tinyint , [bool_col2] tinyint NULL);INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) OUTPUT INSERTED.[int_col],INSERTED.[int_col2],INSERTED.[tinyint_col],INSERTED.[smallint_col],INSERTED.[char_col],INSERTED.[char_col2],INSERTED.[char_col3],INSERTED.[float_col],INSERTED.[float_col2],INSERTED.[blob_col],INSERTED.[numeric_col],INSERTED.[time],INSERTED.[bool_col],INSERTED.[bool_col2] INTO @temporary_inserted VALUES (:qp0, now());SELECT * FROM @temporary_inserted;
                SQL,
                [':qp0' => null],
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id], [col]) OUTPUT INSERTED.[id],INSERTED.[email],INSERTED.[name],INSERTED.[address],INSERTED.[status],INSERTED.[profile_id] INTO @temporary_inserted VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
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
                (new Query($db))
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int , [email] varchar(128) , [name] varchar(128) NULL, [address] text NULL, [status] int NULL, [profile_id] int NULL);INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id],INSERTED.[email],INSERTED.[name],INSERTED.[address],INSERTED.[status],INSERTED.[profile_id] INTO @temporary_inserted SELECT [email], [name], [address], [is_active], [related_id] FROM [customer] WHERE ([email]=:qp1) AND ([name]=:qp2) AND ([address]=:qp3) AND ([is_active]=:qp4) AND ([related_id] IS NULL) AND ([col]=CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
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
        $selectExist = parent::selectExist();

        $selectExist[0][1] = <<<SQL
        SELECT CASE WHEN EXISTS(SELECT 1 FROM `table` WHERE `id` = 1) THEN 1 ELSE 0 END
        SQL;

        return $selectExist;
    }

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

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }
}
