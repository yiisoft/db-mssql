<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Provider\BaseCommandProvider;

use function json_encode;
use function serialize;

final class CommandProvider
{
    use TestTrait;

    public function alterColumn(): array
    {
        return [
            [
                <<<SQL
                ALTER TABLE [table] ALTER COLUMN [column] int
                SQL,
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function batchInsertSql(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        $batchInsertSql = $baseCommandProvider->batchInsertSql($this->getConnection());

        $batchInsertSql['multirow']['expectedParams'][':qp1'] = '0.0';
        $batchInsertSql['multirow']['expectedParams'][':qp3'] = 1;
        $batchInsertSql['multirow']['expectedParams'][':qp5'] = '0';
        $batchInsertSql['multirow']['expectedParams'][':qp7'] = 0;

        $batchInsertSql['issue11242']['expectedParams'][':qp1'] = '1.1';
        $batchInsertSql['issue11242']['expectedParams'][':qp3'] = 1;

        $batchInsertSql['wrongBehavior']['expectedParams'][':qp1'] = '0.0';
        $batchInsertSql['wrongBehavior']['expectedParams'][':qp3'] = 0;

        $batchInsertSql['batchInsert binds params from expression']['expectedParams'][':qp1'] = '1';
        $batchInsertSql['batchInsert binds params from expression']['expectedParams'][':qp3'] = 0;

        return $batchInsertSql;
    }

    public function bindParamsNonWhere(): array
    {
        return[
            [
                <<<SQL
                SELECT SUBSTRING(name, :len, 6) AS name FROM {{customer}} WHERE [[email]] = :email GROUP BY name
                SQL,
            ],
            [
                <<<SQL
                SELECT SUBSTRING(name, :len, 6) as name FROM {{customer}} WHERE [[email]] = :email ORDER BY name
                SQL,
            ],
            [
                <<<SQL
                SELECT SUBSTRING(name, :len, 6) FROM {{customer}} WHERE [[email]] = :email
                SQL,
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function createIndex(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->createIndex($this->getConnection());
    }

    public function dataInsertVarbinary(): array
    {
        return [
            [
                json_encode(['string' => 'string', 'integer' => 1234]),
                json_encode(['string' => 'string', 'integer' => 1234]),
            ],
            [
                serialize(['string' => 'string', 'integer' => 1234]),
                new Param(serialize(['string' => 'string', 'integer' => 1234]), PDO::PARAM_LOB),
            ],
            [
                'simple string',
                'simple string',
            ],
        ];
    }

    public function rawSql(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->rawSql();
    }

    /**
     * @throws Exception
     */
    public function update(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->update($this->getConnection());
    }

    /**
     * @throws Exception
     */
    public function upsert(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->upsert($this->getConnection());
    }
}
