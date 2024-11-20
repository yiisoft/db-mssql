<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\Tests\Support\TraversableObject;

use function array_replace;

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

    public static function buildCondition(): array
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

        unset($buildCondition['inCondition-custom-2'], $buildCondition['inCondition-custom-6']);

        return $buildCondition;
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO [customer] DEFAULT VALUES
        SQL;

        return $insert;
    }

    public static function insertWithReturningPks(): array
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int );INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id] INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, :qp3, :qp4);SELECT * FROM @temporary_inserted;
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
                INSERT INTO {{%type}} ([related_id], [time]) VALUES (:qp0, now())
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int );INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id], [col]) OUTPUT INSERTED.[id] INTO @temporary_inserted VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int );INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id] INTO @temporary_inserted SELECT [email], [name], [address], [is_active], [related_id] FROM [customer] WHERE ([email]=:qp1) AND ([name]=:qp2) AND ([address]=:qp3) AND ([is_active]=:qp4) AND ([related_id] IS NULL) AND ([col]=CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
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
            [
                '{{%order_item}}',
                ['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 1.0],
                [],
                <<<SQL
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([order_id] int , [item_id] int );INSERT INTO {{%order_item}} ([order_id], [item_id], [quantity], [subtotal]) OUTPUT INSERTED.[order_id],INSERTED.[item_id] INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, :qp3);SELECT * FROM @temporary_inserted;
                SQL,
                [':qp0' => 1, ':qp1' => 1, ':qp2' => 1, ':qp3' => 1.0,],
            ],
        ];
    }

    public static function selectExist(): array
    {
        $selectExist = parent::selectExist();

        $selectExist[0][1] = <<<SQL
        SELECT CASE WHEN EXISTS(SELECT 1 FROM `table` WHERE `id` = 1) THEN 1 ELSE 0 END
        SQL;

        return $selectExist;
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ' .
                    '([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=[EXCLUDED].[address], [status]=[EXCLUDED].[status], [profile_id]=[EXCLUDED].[profile_id] ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email], ' .
                    '[EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);',
            ],

            'regular values with unique at not the first position' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ' .
                    '([address], [email], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=[EXCLUDED].[address], [status]=[EXCLUDED].[status], [profile_id]=[EXCLUDED].[profile_id] ' .
                    'WHEN NOT MATCHED THEN INSERT ([address], [email], [status], [profile_id]) VALUES (' .
                    '[EXCLUDED].[address], [EXCLUDED].[email], [EXCLUDED].[status], [EXCLUDED].[profile_id]);',
            ],

            'regular values with update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ' .
                    '([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=:qp4, [status]=:qp5, [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) ' .
                    'VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);',
            ],

            'regular values without update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ' .
                    '([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) ' .
                    'VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);',
            ],

            'query' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING ' .
                    '(SELECT [email], 2 AS [status] FROM [customer] WHERE [name]=:qp0 ' .
                    'ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ' .
                    '([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [status]=[EXCLUDED].[status] ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);',
            ],

            'query with update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] ' .
                    'WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ' .
                    '([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [address]=:qp1, [status]=:qp2, [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);',
            ],

            'query without update part' => [
                3 => 'MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] ' .
                    'WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ' .
                    '([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);',
            ],

            'values and expressions' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)')],
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS [EXCLUDED] ' .
                    '([email], [ts]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [ts]=[EXCLUDED].[ts] ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES ([EXCLUDED].[email], [EXCLUDED].[ts]);',
            ],

            'values and expressions with update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)')],
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS [EXCLUDED] ' .
                    '([email], [ts]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES ([EXCLUDED].[email], [EXCLUDED].[ts]);',
            ],

            'values and expressions without update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)')],
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS [EXCLUDED] ' .
                    '([email], [ts]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES ([EXCLUDED].[email], [EXCLUDED].[ts]);',
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
                    'AS [EXCLUDED] ([email], [[ts]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) ' .
                    'WHEN MATCHED THEN UPDATE SET [ts]=:qp1, [orders]=T_upsert.orders + 1 ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [[ts]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[ts]]);',
            ],

            'query, values and expressions without update part' => [
                1 => (new Query(self::getDb()))
                        ->select(
                            [
                                'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                                '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                            ],
                        ),
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], CONVERT(bigint, CURRENT_TIMESTAMP) AS [[ts]]) ' .
                    'AS [EXCLUDED] ([email], [[ts]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email], [[ts]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[ts]]);',
            ],
            'no columns to update' => [
                3 => 'MERGE [T_upsert_1] WITH (HOLDLOCK) USING (VALUES (:qp0)) AS [EXCLUDED] ' .
                    '([a]) ON ([T_upsert_1].[a]=[EXCLUDED].[a]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([a]) VALUES ([EXCLUDED].[a]);',
            ],
            'no columns to update with unique' => [
                3 => 'MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0)) AS [EXCLUDED] ' .
                    '([email]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) ' .
                    'WHEN NOT MATCHED THEN INSERT ([email]) VALUES ([EXCLUDED].[email]);',
            ],
            'no unique columns in table - simple insert' => [
                3 => 'INSERT INTO {{%animal}} ([type]) VALUES (:qp0)',
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
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
        $values['datetime()'][0] = 'datetime2(0)';
        $values['datetime(6)'][0] = 'datetime2(6)';
        $values['datetime(null)'][0] = 'datetime2';
        $values['timestamp()'][0] = 'datetime2(0)';
        $values['timestamp(6)'][0] = 'datetime2(6)';
        $values['timestamp(null)'][0] = 'datetime2';
        $values['uuid()'][0] = 'uniqueidentifier';
        $values['array()'][0] = 'nvarchar(max)';
        $values['structured()'][0] = 'nvarchar(max)';
        $values["structured('json')"] = ['varbinary(max)', ColumnBuilder::structured('varbinary(max)')];
        $values['json()'][0] = 'nvarchar(max)';
        $values['json(100)'][0] = 'nvarchar(max)';
        $values["extra('NOT NULL')"][0] = 'nvarchar(255) NOT NULL';
        $values["extra('')"][0] = 'nvarchar(255)';
        $values["check('value > 5')"][0] = 'int CHECK ([col_59] > 5)';
        $values["check('')"][0] = 'int';
        $values['check(null)'][0] = 'int';
        $values["comment('comment')"][0] = 'nvarchar(255)';
        $values["comment('')"][0] = 'nvarchar(255)';
        $values['comment(null)'][0] = 'nvarchar(255)';
        $values["defaultValue('value')"][0] = "nvarchar(255) DEFAULT 'value'";
        $values["defaultValue('')"][0] = "nvarchar(255) DEFAULT ''";
        $values['defaultValue(null)'][0] = 'nvarchar(255) DEFAULT NULL';
        $values['defaultValue($expression)'][0] = 'int DEFAULT (1 + 2)';
        $values["integer()->defaultValue('')"][0] = 'int DEFAULT NULL';
        $values['notNull()'][0] = 'nvarchar(255) NOT NULL';
        $values['null()'][0] = 'nvarchar(255) NULL';
        $values['integer()->primaryKey()'][0] = 'int PRIMARY KEY';
        $values['size(10)'][0] = 'nvarchar(10)';
        $values['unique()'][0] = 'nvarchar(255) UNIQUE';
        $values['unsigned()'][0] = 'int';
        $values['integer(8)->scale(2)'][0] = 'int';
        $values['reference($reference)'][0] = 'int REFERENCES [ref_table] ([id]) ON DELETE CASCADE ON UPDATE CASCADE';
        $values['reference($referenceWithSchema)'][0] = 'int REFERENCES [ref_schema].[ref_table] ([id]) ON DELETE CASCADE ON UPDATE CASCADE';

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
}
